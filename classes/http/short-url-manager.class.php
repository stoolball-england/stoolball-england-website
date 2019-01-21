<?php
require_once('data/data-manager.class.php');
require_once('http/short-url.class.php');
require_once('http/short-url-format.class.php');

/*

How short URLs work
-------------------
1.  Edit control preserves existing short url, if any

2.  Run EnsureShortUrl on object

2.1  If there's an existing short URL, and regeneration not requested, return the URL unaltered
2.2  Generate new short URLs until there's one that isn't taken

3.	Update object in db

4.	Optionally update any related objects in db with related short URLs, then repeat 2.2

5.  Save new short URLs to database, deleting any old short URL for the same thing
*/


/**
 * Create short URLs managed by mod_rewrite
 *
 */
class ShortUrlManager extends DataManager
{
	private $a_formats = array();

	/**
	 * ShortUrlFormat currently being processed
	 *
	 * @var ShortUrlFormat
	 */
	private $o_current_format;
	private $regenerating = false;

	/**
	 * Instantiates a ShortUrlManager
	 *
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 */
	public function __construct(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::__construct($o_settings, $o_db);
		$this->SetItemClass('ShortUrl');
		$a_formats = $this->GetSettings()->GetShortUrlFormats();
		foreach ($a_formats as $o_format) $this->AddUrlFormat($o_format);
	}

	/**
	 * Adds a short URL format to be processed by the ShortUrlManager
	 *
	 * @param ShortUrlFormat $o_format
	 */
	public function AddUrlFormat(ShortUrlFormat $o_format)
	{
		if (!$o_format->GetTable()) throw new Exception('URL format must have a table name');
		if (!$o_format->GetShortUrlField()) throw new Exception('URL format must have a short URL field');

		$this->a_formats[] = $o_format;
	}

	/**
	 * Gets the currently configured short URL formats
	 *
	 * @return ShortUrlFormat[]
	 */
	public function GetUrlFormats()
	{
		return $this->a_formats;
	}

	/**
	 * If the current request matches a short URL, returns an array with details of the real URL in keys names 'script', 'param_names', and 'param_values'
	 *
	 * @return array
	 */
	public function ParseRequestUrl()
	{
		# get requested url, ignoring any query string
		$url_to_check = trim($_SERVER['REQUEST_URI'], '/');
		$url_to_check = rtrim(substr($url_to_check, 0, strlen($url_to_check)-strlen($_SERVER['QUERY_STRING'])), '?');

		# short URLs are limited to safe characters, so if there's anything else in the REQUEST_URI don't even try
		if (preg_match('/[^a-z0-9\/.-]/i', $url_to_check)) return;

		# Look for the url in the db. If not found, try removing folders from the end as they may be RESTful parameters
		$sql_start = 'SELECT script, param_names, param_values FROM ' . $this->GetSettings()->GetTable('ShortUrl') . ' WHERE short_url = ';
		$chopped_url = explode("/", strtolower($url_to_check));
		$rest_params = array();
		$real_url = null;
		do
		{
			$sql = $sql_start . Sql::ProtectString($this->GetDataConnection(), implode("/", $chopped_url));
			$result = $this->GetDataConnection()->query($sql);
			$row = $result->fetch();

			if ($row)
			{
				# Found a match in the short URL cache
				$real_url = array();
				$real_url['script'] = $row->script;
				$real_url['param_names'] = explode('|', $row->param_names);
				$real_url['param_values'] = explode('|', $row->param_values);
				break;
			}
			else
			{
				# No match, so try removing a folder until everything's gone
				array_unshift($rest_params, array_pop($chopped_url));
			}
		}
		while (count($chopped_url) > 0); # Loop if there's still something left to try

		$result->closeCursor();

		$len_rest = count($rest_params);
		if ($real_url != null and $len_rest)
		{
			# add any REST params to page URL
			$real_url['param_names'][] = "params";
			$real_url['param_values'][] = implode("/", $rest_params);
		}
        
		return $real_url;
	}


	/**
	 * Puts manager into mode ready to update the list of active short URLs
	 */
	public function RegenerateCache()
	{
		# Note that we're in the process of regenerating. This is used to skip steps elsewhere.
		$this->regenerating = true;
	}

	/**
	 * Reads all short URLs from the database
	 * @param string $limit_clause
	 */
	public function ReadAllByFormat(ShortUrlFormat $o_format, $limit_clause)
	{
		$this->Clear();

		$this->o_current_format = $o_format;

		$s_sql = 'SELECT ' . $this->o_current_format->GetShortUrlField();
		foreach ($this->o_current_format->GetParameterFields() as $s_field)
		{
			if ($s_field) $s_sql .= ', ' . $s_field;
		}
		$s_sql .= ' FROM ' . $this->o_current_format->GetTable() . " WHERE " . $this->o_current_format->GetShortUrlField() . " IS NOT NULL 
		          ORDER BY " . $this->o_current_format->GetShortUrlField() . $limit_clause;

		$o_result = $this->GetDataConnection()->query($s_sql);

		$this->BuildItems($o_result);

		unset($o_result);
	}

	/**
	 * Builds the short URL data from the database into a collection of short URLs
	 *
	 * @param MySqlRawData $o_result
	 */
	public function BuildItems(MySqlRawData $o_result)
	{
		if ($this->o_current_format instanceof ShortUrlFormat)
		{
			$s_url_field = $this->o_current_format->GetShortUrlField();
			while ($o_row = $o_result->fetch())
			{
				if (isset($o_row->$s_url_field))
				{
					$url = new ShortUrl();
					$url->SetFormat($this->o_current_format);
					$url->SetShortUrl($o_row->$s_url_field);

					$a_param_values = array();
					foreach ($this->o_current_format->GetParameterFields() as $s_param_field)
					{
						$s_param_field = (string)$s_param_field;
						if (isset($o_row->$s_param_field)) $a_param_values[] = $o_row->$s_param_field;
					}
					$url->SetParameterValues($a_param_values);

					$this->Add($url);
				}
			}
		}
	}

	/**
	 * Saves a short URL into the short URL cache
	 *
	 * @param ShortUrl $short_url
	 */
	public function Save($short_url)
	{
		$url_table = $this->GetSettings()->GetTable('ShortUrl');

		$a_format_urls = $short_url->GetFormat()->GetDestinationUrls();
		foreach ($a_format_urls as $s_short_url_pattern => $s_destination_url)
		{
			# Need the above two parameters as a minimum for a short URL to work
			if ($short_url->GetShortUrl() and $s_destination_url)
			{
				$short_url_instance = str_replace('{0}', $short_url->GetShortUrl(), $s_short_url_pattern);
				$destination_url_instance = ltrim($short_url->ApplyParameters($s_destination_url), '/');

				# Now break up the destination URL so we can get all the URL parameters separated
				$url_bits = explode('?', $destination_url_instance, 2);
				$query_bits = (count($url_bits) > 1) ? explode('&', $url_bits[1]) : array();
				$parameters = array();
				$values = array();
				foreach ($query_bits as $name_value_pair)
				{
					$pair = explode('=', $name_value_pair, 2);
					$parameters[] = $pair[0];
					$values[] = $pair[1];
				}

				# If this is a changed, rather than new, short URL, remove the old one from the database cache.
				# But not if we're running RegenerateCache, because that's just deleted everything in the table
				# so it'll be quicker if we don't run this query a few thousand times.
				if (!$this->regenerating)
				{
					$sql = 'DELETE FROM ' . $url_table .
					' WHERE script = ' . Sql::ProtectString($this->GetDataConnection(), $url_bits[0], false) .
					' AND param_names = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $parameters)) .
					' AND param_values = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $values));

					$this->GetDataConnection()->query($sql);
				}

				# Now save the new one
				$sql = "REPLACE INTO $url_table SET short_url = " . Sql::ProtectString($this->GetDataConnection(), $short_url_instance, false) . ", " .
				'short_url_base = ' . Sql::ProtectString($this->GetDataConnection(), $short_url->GetShortUrl(), false) . ', ' .
				'script = ' . Sql::ProtectString($this->GetDataConnection(), $url_bits[0], false) . ", " .
				'param_names = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $parameters)) . ', ' .
				'param_values = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $values));

				$this->GetDataConnection()->query($sql);
			}
		}
	}

    /**
     * When updating a short URL, if child items have short URLs beginning with the URL of the parent, update those too
     * @param ShortUrlFormat $child_item_format
     * @param string $old_prefix The old short URL for the parent
     * @param string $new_prefix The new short URL for the parent
     * @return void
     */
    public function ReplacePrefixForChildUrls(ShortUrlFormat $child_item_format, $old_prefix, $new_prefix) 
    {
        $table = $child_item_format->GetTable();
        $field = $child_item_format->GetShortUrlField();
        $old_prefix = $this->SqlString($old_prefix . "/%");
        $new_prefix = $this->SqlString($new_prefix);
        
        $sql = "UPDATE $table SET
                $field = CONCAT($new_prefix, RIGHT($field,CHAR_LENGTH($field)-LOCATE('/',$field)+1))
                WHERE $field LIKE $old_prefix";
                         
        $this->LoggedQuery($sql);

        if ($this->GetDataConnection()->GetAffectedRows())
        {
            $sql = " UPDATE nsa_short_url SET 
            short_url = CONCAT($new_prefix, RIGHT(short_url,CHAR_LENGTH(short_url)-LOCATE('/',short_url)+1)), 
            short_url_base = CONCAT($new_prefix, RIGHT(short_url_base,CHAR_LENGTH(short_url_base)-LOCATE('/',short_url_base)+1))
            WHERE short_url_base LIKE $old_prefix";
             
            $this->LoggedQuery($sql);
        }
    }

	/**
	 * Delete all variants of a short URL from the short URL cache
	 *
	 * @param string $short_url
	 */
	public function Delete($short_url)
	{
		$sql = 'DELETE FROM ' . $this->GetSettings()->GetTable('ShortUrl') . ' WHERE short_url_base = ' . Sql::ProtectString($this->GetDataConnection(), $short_url, false);
		$this->GetDataConnection()->query($sql);
	}

	/**
	 * Checks whether a short URL is already in use. If info on page supplied, checks whether URL is used by *another* page.
	 *
	 * @param string/ShortUrl $short_url
	 * @return bool
	 */
	public function IsUrlTaken($short_url)
	{
		$taken = false;
		if ($short_url instanceof ShortUrl)
		{
			$a_format_urls = $short_url->GetFormat()->GetDestinationUrls();
			foreach ($a_format_urls as $s_short_url_pattern => $s_destination_url)
			{
				# Need the above two parameters as a minimum for a short URL to work
				if ($short_url->GetShortUrl() and $s_destination_url)
				{
					$short_url_instance = str_replace('{0}', $short_url->GetShortUrl(), $s_short_url_pattern);
					$destination_url_instance = ltrim($short_url->ApplyParameters($s_destination_url), '/');

					# Now break up the destination URL so we can get all the URL parameters separated
					$url_bits = explode('?', $destination_url_instance, 2);
					$query_bits = (count($url_bits) > 1) ? explode('&', $url_bits[1]) : array();
					$parameters = array();
					$values = array();
					foreach ($query_bits as $name_value_pair)
					{
						$pair = explode('=', $name_value_pair, 2);
						$parameters[] = $pair[0];
						$values[] = $pair[1];
					}

					# A short URL pattern of {0} is the base URL, from which others are derived. Check whether it exists
					# for any object other than the current one. If it exists for the current object, that's OK.
					if (strlen($s_short_url_pattern) == 3)
					{
						$sql = 'SELECT short_url FROM ' . $this->GetSettings()->GetTable('ShortUrl') . ' WHERE ' .
							'short_url = ' . Sql::ProtectString($this->GetDataConnection(), $short_url->GetShortUrl(), false) .
							" AND NOT (" .
								' script = ' . Sql::ProtectString($this->GetDataConnection(), $url_bits[0], false) .
								' AND param_names = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $parameters)) .
								' AND param_values = ' . Sql::ProtectString($this->GetDataConnection(), join('|', $values)) .
							")";
					}
					else
					{
						# Must be a URL which is a derivative of the base URL. Since the above check should confirm the base URL
						# is available for this object, if this derivative URL exists and is tied to this base URL, that's OK.
						# If it exists and is tied to another base URL though, then we have a problem.

						$sql = 'SELECT short_url FROM ' . $this->GetSettings()->GetTable('ShortUrl') . ' WHERE ' .
							'short_url = ' . Sql::ProtectString($this->GetDataConnection(), $short_url_instance, false) .
							' AND NOT short_url_base = ' . Sql::ProtectString($this->GetDataConnection(), $short_url->GetShortUrl(), false);
					}

					$result = $this->GetDataConnection()->query($sql);
					$taken = (bool)$result->fetch();
					$result->closeCursor();

					if ($taken) break; // only need to find once
				}
			}
		}
		else
		{
			$sql = 'SELECT short_url FROM ' . $this->GetSettings()->GetTable('ShortUrl') . ' WHERE short_url = ' . Sql::ProtectString($this->GetDataConnection(), $short_url, false);
			$result = $this->GetDataConnection()->query($sql);
			$taken = (bool)$result->fetch();
			$result->closeCursor();
		}

		return $taken;
	}

	/**
	 * Generate a short URL for an object if none exists
	 *
	 * @param IHasShortUrl $object
	 * @param bool $b_regenerate
	 * @return ShortUrl
	 */
	public function EnsureShortUrl(IHasShortUrl $object, $b_regenerate=false)
	{
		if (!$object->GetShortUrl() or $b_regenerate)
		{
			$i_preference = 0;
			$current_url = $object->GetShortUrl();
			$object->SetShortUrl('');

			do
			{
				$i_preference++;
				$object->SetShortUrl($object->SuggestShortUrl($i_preference));
				if ($b_regenerate and $object->GetShortUrl() == $current_url) return; # if regenerating, it's ok to keep the current URL if it's still appropriate

			} while ($this->IsUrlTaken($object->GetShortUrl()));
		}

		# Now add the new version, but if this is a new item there's a problem.

		# This method is run before adding the new item to the database, so that we
		# can write the item's short URL to the database at the same time as the
		# rest of the item. But that means the item doesn't have an id yet, and
		# generally we want to use the id. So, return the ShortUrl object so that it
		# can be updated after the item has been inserted into the database.
		$short_url = new ShortUrl($object->GetShortUrl());
		$short_url->SetFormat($object->GetShortUrlFormat()); # Use instance, not static, method so object has a chance to customise format
		$short_url->SetParameterValuesFromObject($object);
		return $short_url;
	}

    /**
     * Regenerate the URL, accepting the current one may still be valid, save it and update the original object
     * @param IHasShortUrl $object
     */
    public function EnsureShortUrlAndSave(IHasShortUrl $object)
    {
        $new_short_url = $this->EnsureShortUrl($object, true);
        if (is_object($new_short_url))
        {
            $new_short_url->SetParameterValuesFromObject($object);
            $this->Save($new_short_url);
        }    
    }
}
?>
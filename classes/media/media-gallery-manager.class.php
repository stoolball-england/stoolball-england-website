<?php
require_once('data/data-manager.class.php');
require_once('media/media-gallery.class.php');
require_once('xhtml/xhtml-image.class.php');
require_once('stoolball/season.class.php');
require_once('stoolball/team.class.php');

class MediaGalleryManager extends DataManager
{
	/**
	 * @return MediaMediaGalleryManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write media galleries
	 */
	function MediaGalleryManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'MediaGallery';
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the objects matching the supplied ids
	 */
	function ReadById($a_ids=null)
	{
		# build query
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$s_people = $this->GetSettings()->GetTable('User');
		$s_image = $this->GetSettings()->GetTable('Image');

		$s_sql = "SELECT $s_gallery.gallery_id, $s_gallery.gallery_title, $s_gallery.gallery_desc, $s_gallery.short_url, " .
		"$s_gallery_link.item_id, $s_gallery_link.item_type, " .
		"$s_people.user_id AS added_by, $s_people.known_as AS added_by_name, " .
		"$s_image.image_id AS cover_id, $s_image.url AS cover_url, $s_image.thumb_url AS cover_thumb_url, $s_image.alternate AS cover_alternate, $s_image.longdesc AS cover_longdesc " .
		"FROM (($s_gallery INNER JOIN $s_people ON $s_gallery.added_by = $s_people.user_id) " .
		'LEFT OUTER JOIN ' . $s_gallery_link . ' ON ' . $s_gallery . '.gallery_id = ' . $s_gallery_link . '.gallery_id) ' .
		"LEFT OUTER JOIN $s_image ON $s_gallery.cover_image = $s_image.image_id ";

		# limit to specific objects, if specified
		$where = '';
		if (is_array($a_ids)) $where = $this->SqlAddCondition($where, "$s_gallery.gallery_id IN (" . join(', ', $a_ids) . ') ');
		$s_sql = $this->SqlAddWhereClause($s_sql, $where);

		$s_sql .= 'ORDER BY ' . $s_gallery . '.gallery_title ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into object
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the objects matching the supplied ids
	 */
	function ReadImagesByGalleryId($a_ids)
	{
		# build query
		$this->ValidateNumericArray($a_ids);
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$i_gallery_link_key = ContentType::IMAGE;
		$s_image = $this->GetSettings()->GetTable('Image');

		$s_sql = "SELECT $s_gallery.gallery_id, $s_gallery.gallery_title, $s_gallery.gallery_desc, $s_gallery.short_url, $s_gallery.added_by, " .
		"$s_gallery_link.short_url AS short_url_in_gallery, " .
		$s_image . '.image_id, ' . $s_image . '.url, ' . $s_image . '.thumb_url, ' . $s_image . '.alternate, ' . $s_image . '.longdesc ' .
		'FROM (' . $s_gallery . ' LEFT OUTER JOIN ' . $s_gallery_link . ' ON ' . $s_gallery . '.gallery_id = ' . $s_gallery_link . '.gallery_id AND ' . $s_gallery_link . '.item_type = ' . $i_gallery_link_key . ') ' .
		'LEFT OUTER JOIN ' . $s_image . ' ON ' . $s_gallery_link . '.item_id = ' . $s_image . '.image_id AND ' . $s_gallery_link . '.item_type = ' . $i_gallery_link_key . ' ';

		# limit to specific objects, if specified
		$where = '';
		if (is_array($a_ids)) $where = $this->SqlAddCondition($where, "$s_gallery.gallery_id IN (" . join(', ', $a_ids) . ') ');
		$s_sql = $this->SqlAddWhereClause($s_sql, $where);

		$s_sql .= "ORDER BY $s_gallery_link.sort_override ";

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into object
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	  * @access public
	 * @return void
	 * @param int $i_related_item_id
	 * @param ContentType $i_content_type
	 * @desc Read from the db the objects matching the supplied related item id and type
	 */
	function ReadByRelatedItemId($i_related_item_id, $i_content_type)
	{
		# check parameters
		if (!is_numeric($i_content_type) or !is_numeric($i_content_type)) die('Invalid arguments when getting media galleries');

		# build query
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$s_image = $this->GetSettings()->GetTable('Image');

		$s_sql = "SELECT $s_gallery.gallery_id, $s_gallery.gallery_title, $s_gallery.gallery_desc, $s_gallery.short_url, " .
		"$s_image.image_id AS cover_id, $s_image.url AS cover_url, $s_image.thumb_url AS cover_thumb_url, $s_image.alternate AS cover_alternate, $s_image.longdesc AS cover_longdesc " .
		'FROM (' . $s_gallery . ' INNER JOIN ' . $s_gallery_link . ' ON ' . $s_gallery . '.gallery_id = ' . $s_gallery_link . '.gallery_id) ' .
		"LEFT OUTER JOIN $s_image ON $s_gallery.cover_image = $s_image.image_id ";

		$where = $this->SqlAddCondition('', $s_gallery_link . '.item_id = ' . Sql::ProtectNumeric($i_related_item_id));
		$where = $this->SqlAddCondition($where, $s_gallery_link . '.item_type = ' . Sql::ProtectNumeric($i_content_type), $this->SqlAnd());
		$s_sql = $this->SqlAddWhereClause($s_sql, $where);

		$s_sql .= 'ORDER BY ' . $s_gallery . '.gallery_title ASC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into object
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * Populates the collection of business objects from raw data
	 *
	 * @return bool
	 * @param MySqlRawData $o_result
	 */
	protected function BuildItems(MySqlRawData $o_result)
	{
		# use CollectionBuilder to handle duplicates
		$o_gallery_builder = new CollectionBuilder();
		$image_builder = new CollectionBuilder();
		$season_builder = new CollectionBuilder();
		$team_builder = new CollectionBuilder();
		$o_gallery = null;

		while($o_row = $o_result->fetch())
		{
			# check whether this is a new gallery
			if (!$o_gallery_builder->IsDone($o_row->gallery_id))
			{
				# store any exisiting gallery
				if ($o_gallery != null)
				{
					$this->Add($o_gallery);
					$image_builder->Reset();
					$season_builder->Reset();
					$team_builder->Reset();
				}

				# create the new gallery
				$o_gallery = new MediaGallery($this->o_settings);
				$o_gallery->SetId($o_row->gallery_id);
				$o_gallery->SetTitle($o_row->gallery_title);
				$o_gallery->SetDescription($o_row->gallery_desc);
				$o_gallery->SetShortUrl($o_row->short_url);
				if (isset($o_row->added_by))
				{
					$added_by = new User();
					$added_by->SetId($o_row->added_by);
					if (isset($o_row->added_by_name)) $added_by->SetName($o_row->added_by_name);
					$o_gallery->SetAddedBy($added_by);
				}
				if (isset($o_row->cover_id) and is_numeric($o_row->cover_id))
				{
					$cover = new XhtmlImage($this->GetSettings(), $o_row->cover_url);
					$cover->SetId($o_row->cover_id);
					$cover->SetThumbnailUrl($o_row->cover_thumb_url);
					$cover->SetDescription($o_row->cover_alternate);
					$cover->SetLongDescription($o_row->cover_longdesc);
					$o_gallery->SetCoverImage($cover);
					unset($cover);
				}
			}

			# images a cause of multiple rows
			if (isset($o_row->image_id) and is_numeric($o_row->image_id) and !$image_builder->IsDone($o_row->image_id))
			{
				$o_image = new XhtmlImage($this->GetSettings(), $o_row->url);
				$o_image->SetId($o_row->image_id);
				$o_image->SetThumbnailUrl($o_row->thumb_url);
				$o_image->SetDescription($o_row->alternate);
				$o_image->SetLongDescription($o_row->longdesc);
				$o_gallery->Images()->Add(new GalleryItem($o_image, $o_row->short_url_in_gallery));
				unset($o_image);
			}

			if (isset($o_row->item_type) and isset($o_row->item_id))
			{
				# seasons a cause of multiple rows
				if ($o_row->item_type == ContentType::STOOLBALL_SEASON and !$season_builder->IsDone($o_row->item_id))
				{
					$season = new Season($this->GetSettings());
					$season->SetId($o_row->item_id);
					$o_gallery->RelatedItems()->Add($season);
					unset($season);
				}

				# teams another cause of multiple rows
				else if ($o_row->item_type == ContentType::STOOLBALL_TEAM and !$team_builder->IsDone($o_row->item_id))
				{
					$team = new Team($this->GetSettings());
					$team->SetId($o_row->item_id);
					$o_gallery->RelatedItems()->Add($team);
					unset($team);
				}
			}
		}
		# store final gallery
		if ($o_gallery != null) $this->Add($o_gallery);

		return true;
	}

	/**
	 * @return int
	 * @param MediaGallery $o_object
	 * @desc Save the supplied MediaGallery to the database, and return the id
	 */
	function Save($o_object)
	{
		/* @var $o_object MediaGallery */

		# check parameters
		if (!$o_object instanceof MediaGallery) throw new Exception('Unable to save');

		# Set up short URL manager
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
		$gallery_short_url = $o_url_manager->EnsureShortUrl($o_object);

		# build query
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');

		# If no cover image, use the first one
		$i_cover_image = null;
		if ($o_object->HasCoverImage())
		{
			$i_cover_image = $o_object->GetCoverImage()->GetId();
		}
		else if ($o_object->Images()->GetCount())
		{
			$o_object->Images()->ResetCounter();
			$i_cover_image = $o_object->Images()->GetFirst()->GetItem()->GetId();
		}


		# if no id, it's a new object; otherwise update the existing object
		if ($o_object->GetId())
		{
			$s_sql = 'UPDATE ' . $s_gallery . ' SET ' .
			"gallery_title = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetTitle()) . ", " .
			"gallery_desc = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetDescription()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetShortUrl()) . ", " .
			"cover_image = " . Sql::ProtectNumeric($i_cover_image, true) . ', ' .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE gallery_id = ' . Sql::ProtectNumeric($o_object->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);

			# clear out existing relationships (to be updated later in the save)
			$s_sql = 'DELETE FROM ' . $s_gallery_link . ' WHERE gallery_id = ' . Sql::ProtectNumeric($o_object->GetId()) . ' ' .
			'AND item_type IN (' . Sql::ProtectNumeric(ContentType::STOOLBALL_SEASON) . ', ' . Sql::ProtectNumeric(ContentType::STOOLBALL_TEAM) . ')';
			$this->GetDataConnection()->query($s_sql);
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $s_gallery . ' SET ' .
			"gallery_title = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetTitle()) . ", " .
			"gallery_desc = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetDescription()) . ", " .
			"short_url = " . Sql::ProtectString($this->GetDataConnection(), $o_object->GetShortUrl()) . ", " .
			"cover_image = " . Sql::ProtectNumeric($i_cover_image, true) . ', ' .
			"added_by " . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId(), false, true) . ', ' .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_object->SetId($this->GetDataConnection()->insertID());
		}

		$o_object->RelatedItems()->ResetCounter();
		foreach ($o_object->RelatedItems() as $item)
		{
			/* @var $item IHasMedia */
			$s_sql = "INSERT INTO $s_gallery_link SET " .
			'gallery_id = ' . Sql::ProtectNumeric($o_object->GetId()) . ', ' .
			'item_id = ' . Sql::ProtectNumeric($item->GetId()) . ', ' .
			'item_type = ' . Sql::ProtectNumeric($item->GetContentType());

			$this->GetDataConnection()->query($s_sql);
		}

		# Update image captions and descriptions
		$s_image = $this->GetSettings()->GetTable('Image');
		$i = 0;
		foreach ($o_object->Images() as $gallery_item)
		{
			$image = $gallery_item->GetItem();

			/* @var $image XhtmlImage */
			$sql = "UPDATE $s_image SET " .
			'alternate = ' . Sql::ProtectString($this->GetDataConnection(), $image->GetDescription()) . ', ' .
			'longdesc = ' . Sql::ProtectString($this->GetDataConnection(), $image->GetLongDescription()) . ', ' .
			'date_changed = ' . Sql::ProtectNumeric(gmdate('U')) .  ' ' .
			'WHERE image_id = ' . Sql::ProtectNumeric($image->GetId(), false);

			$this->GetDataConnection()->query($sql);

			$i++;

			/* @var $item IHasMedia */
			$s_sql = "UPDATE $s_gallery_link SET " .
			'short_url = ' . Sql::ProtectString($this->GetDataConnection(), $o_object->GetShortUrl() . $i) . ', ' .
			'sort_override = ' . $i . ' ' .
			'WHERE gallery_id = ' . Sql::ProtectNumeric($o_object->GetId()) . ' ' .
			'AND item_id = ' . Sql::ProtectNumeric($image->GetId()) . ' ' .
			'AND item_type = ' . Sql::ProtectNumeric(ContentType::IMAGE);

			$this->GetDataConnection()->query($s_sql);

			# Add to short URL cache
			$image_url = new ShortUrl($o_object->GetShortUrl() . $i);
			$image_url->SetFormat(XhtmlImage::GetShortUrlFormatForType($this->GetSettings()));
			$image_url->SetParameterValues(array($o_object->GetId(), $image->GetId()));
			$o_url_manager->Save($image_url);
		}

		# Add gallery URL to short URL cache
		if (is_object($gallery_short_url))
		{
			$gallery_short_url->SetParameterValuesFromObject($o_object);
			$o_url_manager->Save($gallery_short_url);
		}
		unset($o_url_manager);

		return $o_object->GetId();

	}


	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the objects matching the supplied ids
	 */
	function Delete($a_ids)
	{
		# check paramter
		if (!is_array($a_ids)) die('Nothing to delete');

		# build query
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$s_ids = join(', ', $a_ids);

		# delete images in gallery
		require_once('media/image-manager.class.php');
		$image_manager = new ImageManager($this->GetSettings(), $this->GetDataConnection());
		foreach ($a_ids as $gallery_id)
		{
			$s_sql = 'SELECT item_id FROM ' . $s_gallery_link . ' WHERE gallery_id = ' . Sql::ProtectNumeric($gallery_id) . ' AND item_type = ' . ContentType::IMAGE;
			$result = $this->GetDataConnection()->query($s_sql);
			while ($row = $result->fetch())
			{
				$image_manager->DeleteFromGallery(array($row->item_id), $gallery_id);
			}
			$result->closeCursor();
		}

		# delete any other gallery item relationships
		$s_sql = 'DELETE FROM ' . $s_gallery_link . ' WHERE gallery_id IN (' . $s_ids . ')';
		$this->GetDataConnection()->query($s_sql);

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_gallery WHERE gallery_id IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# delete gallery(s)
		$s_sql = 'DELETE FROM ' . $s_gallery . ' WHERE gallery_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}
}
?>
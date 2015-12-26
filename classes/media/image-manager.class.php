<?php
require_once('data/data-manager.class.php');
require_once('xhtml/xhtml-image.class.php');
require_once('media/media-gallery.class.php');
require_once('data/queue-action.enum.php');

class ImageManager extends DataManager
{
	/**
	 * @return ImageManager
	 * @param SiteSettings $o_settings
	 * @param MySqlConnection $o_db
	 * @desc Read and write Images
	 */
	function ImageManager(SiteSettings $o_settings, MySqlConnection $o_db)
	{
		parent::DataManager($o_settings, $o_db);
		$this->s_item_class = 'XhtmlImage';
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Read from the db the Images matching the supplied ids
	 */
	function ReadById($a_ids=null)
	{
		# build query
		$s_image = $this->GetSettings()->GetTable('Image');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_person = $this->GetSettings()->GetTable('User');
		$i_gallery_link_key = ContentType::IMAGE;

		$s_sql = "SELECT $s_image.image_id, $s_image.original_url, $s_image.url, $s_image.thumb_url, $s_image.alternate, $s_image.longdesc, $s_image.date_checked, $s_image.date_uploaded, " .
		"$s_person.user_id AS checked_by, $s_person.known_as AS checked_by_name, " .
		"uploaded.user_id AS uploaded_by, uploaded.known_as AS uploaded_by_name, " .
		"$s_gallery.gallery_id, $s_gallery.gallery_title " .
		'FROM (((' . $s_image . ' LEFT OUTER JOIN ' . $s_gallery_link . ' ON ' . $s_image . '.image_id = ' . $s_gallery_link . '.item_id AND ' . $s_gallery_link . ".item_type = " . $i_gallery_link_key . ") " .
		'LEFT OUTER JOIN ' . $s_gallery . ' ON ' . $s_gallery_link . '.gallery_id = ' . $s_gallery . '.gallery_id AND ' . $s_gallery_link . ".item_type = " . $i_gallery_link_key . ") " .
		"LEFT OUTER JOIN $s_person ON $s_image.checked_by = $s_person.user_id) " .
		"LEFT OUTER JOIN $s_person AS uploaded ON $s_image.uploaded_by = uploaded.user_id ";

		# limit to specific images, if specified
		if (is_array($a_ids)) $s_sql .= 'WHERE ' . $s_image . '.image_id  IN (' . join(', ', $a_ids) . ') ';

		$s_sql .= 'ORDER BY ' . $s_image . '.alternate DESC';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Image
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}

	/**
	 * @access public
	 * @return void
	 * @desc Read from the db the oldest image awaiting approval
	 */
	function ReadNextImageForApproval()
	{
		# Request to this method means image approval visited, so remove block on notification of new images
		$sql = 'DELETE FROM ' . $this->GetSettings()->GetTable('Queue') . " WHERE action = " . QueueAction::IMAGE_NO_CHECK_EMAIL;
		$this->GetDataConnection()->query($sql);

		# build query
		$s_image = $this->GetSettings()->GetTable('Image');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_person = $this->GetSettings()->GetTable('User');
		$i_gallery_link_key = ContentType::IMAGE;

		$s_sql = "SELECT $s_image.image_id, $s_image.original_url, $s_image.url, $s_image.thumb_url, $s_image.alternate, $s_image.longdesc, $s_image.date_checked, $s_image.date_uploaded, " .
		"$s_person.user_id AS checked_by, $s_person.known_as AS checked_by_name, " .
		"uploaded.user_id AS uploaded_by, uploaded.known_as AS uploaded_by_name, " .
		"$s_gallery.gallery_id, $s_gallery.gallery_title " .
		'FROM (((' . $s_image . ' LEFT OUTER JOIN ' . $s_gallery_link . ' ON ' . $s_image . '.image_id = ' . $s_gallery_link . '.item_id AND ' . $s_gallery_link . ".item_type = " . $i_gallery_link_key . ") " .
		'LEFT OUTER JOIN ' . $s_gallery . ' ON ' . $s_gallery_link . '.gallery_id = ' . $s_gallery . '.gallery_id AND ' . $s_gallery_link . ".item_type = " . $i_gallery_link_key . ") " .
		"LEFT OUTER JOIN $s_person ON $s_image.checked_by = $s_person.user_id) " .
		"LEFT OUTER JOIN $s_person AS uploaded ON $s_image.uploaded_by = uploaded.user_id " .
		'WHERE ' . $s_image . '.date_checked IS NULL OR checked_by IS NULL ' .
		'ORDER BY ' . $s_image . '.date_added ASC ' .
		'LIMIT 0,1';

		# run query
		$o_result = $this->GetDataConnection()->query($s_sql);

		# build raw data into Image
		$this->BuildItems($o_result);

		# tidy up
		$o_result->closeCursor();
		unset($o_result);
	}


	/**
	 * If any images are unchecked, notify someone who can check them
	 *
	 * @param string $email_address
	 * @param string $admin_url
	 */
	public function NotifyUncheckedImages($email_address, $admin_url)
	{
		$queue = $this->GetSettings()->GetTable('Queue');
		$result = $this->GetDataConnection()->query('SELECT COUNT(queue_id) AS total FROM ' . $queue . " WHERE action = " . QueueAction::IMAGE_NO_CHECK_EMAIL);
		$row = $result->fetch();
		if (((int)$row->total) == 0)
		{
			$half_hour = (60*30);
			$result = $this->GetDataConnection()->query('SELECT COUNT(image_id) AS total FROM ' . $this->GetSettings()->GetTable('Image') . ' WHERE checked_by IS NULL AND date_changed < ' . (gmdate('U') - $half_hour));
			$row = $result->fetch();
			if (((int)$row->total) > 0)
			{
				require_once 'Zend/Mail.php';
				$email = new Zend_Mail('UTF-8');
				$email->addTo($email_address);
				$email->setFrom($this->GetSettings()->GetEmailAddress(), $this->GetSettings()->GetSiteName());
				$email->setSubject('New photos added to ' . $this->GetSettings()->GetSiteName());
				$email->setBodyText('New photos have been added to ' .  $this->GetSettings()->GetSiteName() . ".\n\nPlease check that they are appropriate.\n\n" . $admin_url);

				try
				{
					$email->send();
					$sql = 'INSERT INTO ' . $queue . ' (action, date_added) VALUES (' . QueueAction::IMAGE_NO_CHECK_EMAIL . ', ' . gmdate('U') . ')';
					$this->GetDataConnection()->query($sql);
				}
				catch (Zend_Mail_Transport_Exception $e)
				{
					# Do nothing - failing to send the email should not be a fatal error. The queue entry will not be added, which means another
					# attempt will be made to send the email when someone next calls this method.
				}

			}
		}
	}


	/**
	 * Queue an image file for later deletion if it can't be deleted at the moment
	 *
	 * @param string $filename
	 */
	public function QueueImageForDeletion($filename)
	{
		if ($filename)
		{
			$sql = 'INSERT INTO ' . $this->GetSettings()->GetTable('Queue') . ' (data, action, date_added) VALUES (' . Sql::ProtectString($this->GetDataConnection(), $filename) . ', ' . QueueAction::IMAGE_DELETE . ', ' . gmdate('U') . ')';
			$this->GetDataConnection()->query($sql);
		}
	}

	/**
	 * Deletes image files from disk that have been queued for deletion
	 *
	 */
	public function DeleteQueuedImages()
	{
		$queue = $this->GetSettings()->GetTable('Queue');
		$results = $this->GetDataConnection()->query("SELECT queue_id, data FROM $queue WHERE action = " . QueueAction::IMAGE_DELETE);
		$ids = array();
		while ($row = $results->fetch())
		{
			if (file_exists($row->data) and !is_dir($row->data)) unlink($row->data);
			$ids[] = $row->queue_id;
		}
		if (count($ids)) $this->GetDataConnection()->query("DELETE FROM $queue WHERE queue_id IN (" . join(', ', $ids) . ')');
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
		$o_image_builder = new CollectionBuilder();
		$o_image = null;

		while($o_row = $o_result->fetch())
		{
			# check whether this is a new image
			if (!$o_image_builder->IsDone($o_row->image_id))
			{
				# store any exisiting image
				if ($o_image != null) $this->Add($o_image);

				# create the new image
				$o_image = new XhtmlImage($this->GetSettings(), $o_row->url);
				$o_image->SetId($o_row->image_id);
				$o_image->SetDescription($o_row->alternate);
				$o_image->SetLongDescription($o_row->longdesc);
				$o_image->SetOriginalUrl($o_row->original_url);
				$o_image->SetThumbnailUrl($o_row->thumb_url);

				if (isset($o_row->date_uploaded) and isset($o_row->uploaded_by) and isset($o_row->uploaded_by_name))
				{
					$o_image->SetUploadedDate($o_row->date_uploaded);
					$o_image->SetUploadedBy(new User($o_row->uploaded_by, $o_row->uploaded_by_name));
				}

				if (isset($o_row->date_checked) and isset($o_row->checked_by) and isset($o_row->checked_by_name) and $o_row->date_checked and $o_row->checked_by)
				{
					$o_image->SetCheckedDate($o_row->date_checked);
					$o_image->SetCheckedBy(new User($o_row->checked_by, $o_row->checked_by_name));
				}
			}

			# gallery the only cause of multiple rows (so far) so add to current image
			if (isset($o_row->gallery_id) and is_numeric($o_row->gallery_id))
			{
				$o_gallery = new MediaGallery($this->o_settings);
				$o_gallery->SetId($o_row->gallery_id);
				$o_gallery->SetTitle($o_row->gallery_title);
				$o_image->AddGallery($o_gallery);
			}
		}
		# store final category
		if ($o_image != null) $this->Add($o_image);

		return true;
	}

	/**
	 * @return int
	 * @param XhtmlImage $o_image
	 * @desc Save the supplied XhtmlImage to the database, and return the id
	 */
	function Save($o_image)
	{
		/* @var $o_image XhtmlImage */

		# check parameters
		if (!$o_image instanceof XhtmlImage) throw new Exception('Unable to save image');

		# build query
		$image_table = $this->GetSettings()->GetTable('Image');
		$s_gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');

		# has image been (re)confirmed as appropriate?
		$s_checked = '';
		if ($o_image->IsChecked())
		{
			$s_checked = 'date_checked = ' . Sql::ProtectNumeric($o_image->GetCheckedDate()) . ', checked_by = ' . Sql::ProtectNumeric($o_image->GetCheckedBy()->GetId()) . ', ';
		}
		else if ($o_image->GetIsNewUpload())
		{
			if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::ApproveImage()))
			{
				$s_checked = 'date_checked = ' . Sql::ProtectNumeric(gmdate('U')) . ', checked_by = ' . AuthenticationManager::GetUser()->GetId() . ', ';
			}
			else
			{
				# If image replaced and not checked, eliminate any previous check
				$s_checked = 'date_checked = NULL, checked_by = NULL, ';
			}
		}

		# is image a new upload
		$s_upload = '';
		if ($o_image->GetIsNewUpload())
		{
			$s_upload = 'original_url = ' . Sql::ProtectString($this->GetDataConnection(), $o_image->GetOriginalUrl(), false) . ', ' .
			'date_uploaded = ' . Sql::ProtectNumeric(gmdate('U')) . ', ' .
			'uploaded_by = ' . AuthenticationManager::GetUser()->GetId() . ', ';
		}

		# if no id, it's a new image; otherwise update the image
		if ($o_image->GetId())
		{
			# If image has just been replaced, delete the existing image
			if ($o_image->GetIsNewUpload()) $this->DeleteFiles(array($o_image->GetId()));

			$s_sql = 'UPDATE ' . $image_table . ' SET ' .
			"url = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetUrl(true), false) . ", " .
			"thumb_url = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetThumbnailUrl(true), false) . ", " .
			"alternate = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetDescription(), false) . ", " .
			"longdesc = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetLongDescription(), false) . ", " .
			$s_upload .
			$s_checked .
			'date_changed = ' . gmdate('U') . ' ' .
			'WHERE image_id = ' . Sql::ProtectNumeric($o_image->GetId());

			# run query
			$this->GetDataConnection()->query($s_sql);

			# clear out galleries this image has been removed from
			$gallery_ids = array();
			foreach ($o_image->GetGalleries() as $gallery) $gallery_ids[] = $gallery->GetId();

			$s_sql = 'SELECT gallery_id FROM ' . $s_gallery_link . ' WHERE item_id = ' . Sql::ProtectNumeric($o_image->GetId()) . ' AND item_type = ' . ContentType::IMAGE;
			if (count($gallery_ids)) $s_sql .= ' AND gallery_id NOT IN (' . join(', ', $gallery_ids) . ')';
			$result = $this->GetDataConnection()->query($s_sql);
			while ($row = $result->fetch())
			{
				$this->DeleteFromGallery(array($o_image->GetId()), $row->gallery_id, false);
			}
			$result->closeCursor();
		}
		else
		{
			$s_sql = 'INSERT INTO ' . $image_table . ' SET ' .
			"url = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetUrl(true), false) . ", " .
			"thumb_url = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetThumbnailUrl(true), false) . ", " .
			"alternate = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetDescription(), false) . ", " .
			"longdesc = " . Sql::ProtectString($this->GetDataConnection(), $o_image->GetLongDescription(), false) . ", " .
			$s_upload .
			$s_checked .
			'date_added = ' . gmdate('U') . ', ' .
			'date_changed = ' . gmdate('U');

			# run query
			$o_result = $this->GetDataConnection()->query($s_sql);

			# get autonumber
			$o_image->SetId($this->GetDataConnection()->insertID());
		}

		# add relationship to gallereis
		require_once('http/short-url-manager.class.php');
		$short_urls = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$a_galleries = $o_image->GetGalleries();
		foreach ($a_galleries as $o_gallery)
		{
			# First check whether image is already in the gallery. If so leave alone to preserve existing sort order etc
			$s_sql = "SELECT item_id FROM $s_gallery_link WHERE" .
			' item_id = ' . Sql::ProtectNumeric($o_image->GetId()) .
			' AND item_type = ' . ContentType::IMAGE .
			' AND gallery_id = ' . Sql::ProtectNumeric($o_gallery->GetId(), false, false);

			$result = $this->GetDataConnection()->query($s_sql);
			if ($result->fetch())
			{
				$result->closeCursor();
				continue;
			}
			else $result->closeCursor();

			# But if not already in gallery, add it to the end of the gallery
			$s_sql = "SELECT MAX($s_gallery_link.sort_override)+1 AS sort, $s_gallery.short_url, $s_gallery.cover_image " .
			"FROM $s_gallery LEFT OUTER JOIN $s_gallery_link ON $s_gallery.gallery_id = $s_gallery_link.gallery_id " . # LEFT JOIN because new gallery has no images yet
			"WHERE $s_gallery.gallery_id = " . Sql::ProtectNumeric($o_gallery->GetId()) . ' ' .
			"GROUP BY $s_gallery.gallery_id";

			$result = $this->GetDataConnection()->query($s_sql);
			if ($row = $result->fetch())
			{
				$sort = is_null($row->sort) ? 1 : (int)$row->sort;

				$s_sql = 'INSERT INTO ' . $s_gallery_link . ' SET ' .
				'gallery_id = ' . Sql::ProtectNumeric($o_gallery->GetId()) . ', ' .
				'item_id = ' . Sql::ProtectNumeric($o_image->GetId()) . ', ' .
				"item_type = " . ContentType::IMAGE . ', ' .
				"short_url = " . Sql::ProtectString($this->GetDataConnection(), $row->short_url . $sort, false) . ', ' .
				"sort_override = " . Sql::ProtectNumeric($sort);

				# run query
				$this->GetDataConnection()->query($s_sql);

				# Add short URL to cache
				$short_url = new ShortUrl($row->short_url . $sort);
				$short_url->SetFormat(XhtmlImage::GetShortUrlFormatForType($this->GetSettings()));
				$short_url->SetParameterValues(array($o_gallery->GetId(), $o_image->GetId()));
				$short_urls->Save($short_url);

				# Make sure the gallery has a cover
				if (!is_numeric($row->cover_image) or $row->cover_image < 1)
				{
					$s_sql = "UPDATE $s_gallery SET cover_image = " . Sql::ProtectNumeric($o_image->GetId()) . ' WHERE gallery_id = ' . Sql::ProtectNumeric($o_gallery->GetId());
					$this->GetDataConnection()->query($s_sql);
				}
			}
		}

		unset($short_urls);

		return $o_image->GetId();

	}

	/**
	 * @return bool
	 * @param string $thumbnail_url
	 * @param string $caption
	 * @desc Save the supplied caption for the image with the specified thumbnail IF the current user is the one who uploaded it
	 */
	function SaveCaption($thumbnail_url, $caption)
	{
		# Check we have two strings to work with
		if (!$thumbnail_url or !$caption) return false;

		# Check url is a thumbnail URL. Have to be flexible about the positon because IE returns an absolute URL from
		# JavaScript whereas everything else sends a relative URL
		$pos = strpos($thumbnail_url, $this->GetSettings()->GetFolder('Images') . 'thumbnails/');
		if ($pos === false) return false;
		$thumbnail_url = substr($thumbnail_url, $pos+strlen($this->GetSettings()->GetFolder('Images')));

		$caption = preg_replace('/\s+/i', ' ', trim($caption)); # normalise space

		$s_sql = 'UPDATE ' . $this->o_settings->GetTable('Image') . ' SET ' .
		'alternate = ' . Sql::ProtectString($this->GetDataConnection(), $caption, false) . ', ' .
		'date_changed = ' . Sql::ProtectNumeric(gmdate('U')) . ' ' .
		'WHERE thumb_url = ' . Sql::ProtectString($this->GetDataConnection(), $thumbnail_url, false) . ' ';

		if (!AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS))
		{
			$s_sql .= 'AND uploaded_by = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId());
		}

		# run query
		$result = $this->GetDataConnection()->query($s_sql);
		$success = ($this->GetDataConnection()->GetAffectedRows() == 1);
		unset($result);
		return $success;
	}

	/**
	 * @return int
	 * @param int[] $a_ids
	 * @desc Confirm that the specified image(s) are appropriate
	 */
	function Approve($a_ids)
	{
		$this->ValidateNumericArray($a_ids);

		$s_sql = 'UPDATE ' . $this->o_settings->GetTable('Image') . ' SET ' .
		'date_checked = ' . Sql::ProtectNumeric(gmdate('U')) . ', ' .
		'checked_by = ' . Sql::ProtectNumeric(AuthenticationManager::GetUser()->GetId()) . ', ' .
		'date_changed = ' . Sql::ProtectNumeric(gmdate('U')) . ' ' .
		'WHERE image_id IN (' . join(', ', $a_ids) . ')';

		# run query
		$this->GetDataConnection()->query($s_sql);
	}

	/**
	 * @access public
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the db the Images matching the supplied ids
	 */
	public function Delete($a_ids)
	{
		# check paramter
		if (!is_array($a_ids)) die('Nothing to delete');

		# build query
		$s_image = $this->GetSettings()->GetTable('Image');
		$s_galleries = $this->GetSettings()->GetTable('MediaGalleryLink');
		$gallery = $this->GetSettings()->GetTable('MediaGallery');
		$s_ids = join(', ', $a_ids);

		# get galleries where this is the cover image
		$s_sql = "SELECT gallery_id FROM $gallery WHERE cover_image IN ($s_ids)";
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			# Get another image from the gallery to use as the cover
			$s_sql = "SELECT $s_galleries.item_id " .
			"FROM $gallery INNER JOIN $s_galleries ON $gallery.gallery_id = $s_galleries.gallery_id " .
			"WHERE $s_galleries.item_type = " . ContentType::IMAGE . ' ' .
			"AND $gallery.gallery_id = " . Sql::ProtectNumeric($row->gallery_id) . ' ' .
			"AND $s_galleries.item_id != $gallery.cover_image " .
			"ORDER BY $s_galleries.item_id DESC LIMIT 0,1";
			$new_cover = $this->GetDataConnection()->query($s_sql);

			if ($cover_row = $new_cover->fetch())
			{
                if (is_numeric($cover_row->item_id) and $cover_row->item_id > 0)
				{
					$s_sql = "UPDATE $gallery SET cover_image = " . Sql::ProtectNumeric($cover_row->item_id) . ' WHERE gallery_id = ' . Sql::ProtectNumeric($row->gallery_id);
					$this->GetDataConnection()->query($s_sql);
				}
				else
				{
					$s_sql = "UPDATE $gallery SET cover_image = NULL WHERE gallery_id = " . Sql::ProtectNumeric($row->gallery_id);
					$this->GetDataConnection()->query($s_sql);
				}
			}
			else
			{
				$s_sql = "UPDATE $gallery SET cover_image = NULL WHERE gallery_id = " . Sql::ProtectNumeric($row->gallery_id);
				$this->GetDataConnection()->query($s_sql);
			}
		}

		# delete from short URL cache
		require_once('http/short-url-manager.class.php');
		$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

		$s_sql = "SELECT short_url FROM $s_galleries WHERE item_id IN ($s_ids) and item_type = " . ContentType::IMAGE;
		$result = $this->GetDataConnection()->query($s_sql);
		while ($row = $result->fetch())
		{
			$o_url_manager->Delete($row->short_url);
		}
		$result->closeCursor();
		unset($o_url_manager);

		# delete gallery relationships
		$s_sql = 'DELETE FROM ' . $s_galleries . ' WHERE item_id IN (' . $s_ids . ") AND item_type = " . ContentType::IMAGE;
		$this->GetDataConnection()->query($s_sql);

		$this->DeleteFiles($a_ids);

		# delete image(s)
		$s_sql = 'DELETE FROM ' . $s_image . ' WHERE image_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);

		return $this->GetDataConnection()->GetAffectedRows();
	}

	/**
	 * Deletes an image from a gallery and, if it's the last gallery the image is in, deletes the image
	 *
	 * @param int[] $a_image_ids
	 * @param int $gallery_id
	 * @param bool $delete_image_if_last_gallery
	 */
	public function DeleteFromGallery($a_image_ids, $gallery_id, $delete_image_if_last_gallery=true)
	{
		if (!count($a_image_ids)) return; # No images to delete!
		$this->ValidateNumericArray($a_image_ids);
		if (!is_numeric($gallery_id)) throw new Exception('A numeric gallery_id must be supplied');
		$gallery_id = (int)$gallery_id;

		$gallery = $this->GetSettings()->GetTable('MediaGallery');
		$gallery_link = $this->GetSettings()->GetTable('MediaGalleryLink');

		# Is the gallery cover one of these images?
		$sql = "SELECT gallery_id FROM $gallery WHERE gallery_id = " . Sql::ProtectNumeric($gallery_id) . ' AND cover_image IN (' . join(', ', $a_image_ids) . ')';
		$result = $this->GetDataConnection()->query($sql);
		
		# If so, assign a new cover
		$row = $result->fetch();
		$deleting_cover = (bool)$row;
		if ($deleting_cover)
		{
			$sql = "SELECT item_id FROM $gallery_link WHERE gallery_id = " . Sql::ProtectNumeric($gallery_id) . ' AND item_type = ' . ContentType::IMAGE . ' ' .
			'AND item_id NOT IN (' . join(', ', $a_image_ids) . ') ORDER BY sort_override ASC LIMIT 0,1';
			$result = $this->GetDataConnection()->query($sql);

			if ($row = $result->fetch())
			{
                $sql = "UPDATE $gallery SET cover_image = " . Sql::ProtectNumeric($row->item_id) . ' WHERE gallery_id = ' . Sql::ProtectNumeric($gallery_id);
				$this->GetDataConnection()->query($sql);
			}
			else
			{
				# Other possibility is that there are no other images in the gallery, in which case remove the cover image
				$sql = "UPDATE $gallery SET cover_image = NULL WHERE gallery_id = " . Sql::ProtectNumeric($gallery_id);
				$this->GetDataConnection()->query($sql);
			}
		}

		# For each image, is it in any other galleries?
		foreach ($a_image_ids as $image_id)
		{
			$sql = "SELECT COUNT(item_id) AS total_galleries FROM $gallery_link WHERE item_id = " . Sql::ProtectNumeric($image_id) . ' AND item_type = ' . ContentType::IMAGE . ' AND gallery_id != ' . Sql::ProtectNumeric($gallery_id);
			$result = $this->GetDataConnection()->query($sql);
			if (!($row = $result->fetch())) throw new Exception('Unable to check whether the image is in other galleries');
			$in_gallery = (bool)$row->total_galleries;

			if ($in_gallery or !$delete_image_if_last_gallery)
			{
				# If yes, or specifically told not to delete image, just delete the relationship to this gallery

				# delete from short URL cache
				require_once('http/short-url-manager.class.php');
				$o_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());

				$s_sql = "SELECT short_url FROM $gallery_link WHERE item_id = " . Sql::ProtectNumeric($image_id) . " AND item_type = " . ContentType::IMAGE;
				$result = $this->GetDataConnection()->query($s_sql);
				while ($row = $result->fetch())
				{
					$o_url_manager->Delete($row->short_url);
				}
				$result->closeCursor();
				unset($o_url_manager);

				# ...and remove from gallery
				$sql = "DELETE FROM $gallery_link WHERE item_id = " . Sql::ProtectNumeric($image_id) . ' AND item_type = ' . ContentType::IMAGE . ' AND gallery_id = ' . Sql::ProtectNumeric($gallery_id);
				$result = $this->GetDataConnection()->query($sql);
			}
			else if ($delete_image_if_last_gallery)
			{
				# If no, delete the image completely
				$this->Delete(array($image_id));
			}
		}
	}

	/**
	 * @return void
	 * @param int[] $a_ids
	 * @desc Delete from the file system the files referenced by the Images with the supplied ids
	 */
	private function DeleteFiles($a_ids)
	{
		# check paramter
		if (!is_array($a_ids)) die('Nothing to delete');

		# build query
		$s_image = $this->o_settings->GetTable('Image');
		$s_ids = join(', ', $a_ids);

		# get urls of the files
		$s_sql = 'SELECT original_url, url, thumb_url FROM ' . $s_image . ' WHERE image_id IN (' . $s_ids . ') ';
		$o_result = $this->GetDataConnection()->query($s_sql);
		$a_files = array();

		if (is_object($o_result))
		{
			while($o_row = $o_result->fetch())
			{
				$a_files[] = $this->GetSettings()->GetFolder('ImagesServer') . $o_row->original_url;
				$a_files[] = $this->GetSettings()->GetFolder('ImagesServer') . $o_row->url;
				$a_files[] = $this->GetSettings()->GetFolder('ImagesServer') . $o_row->thumb_url;
			}
		}

		# delete the files
		foreach($a_files as $s_file)
		{
			if (file_exists($s_file) and !is_dir($s_file))
			{
				unlink($s_file);
			}
		}
	}
}
?>
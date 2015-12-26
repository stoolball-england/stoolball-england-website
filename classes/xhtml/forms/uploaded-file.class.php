<?php
class UploadedFile
{
	var $o_settings;

	var $s_type;
	var $s_file;
	var $s_temp_file;
	var $i_size;

	var $s_upload_dir;
	var $s_file_name;
	var $s_file_ext;

	function UploadedFile(SiteSettings $o_settings, $s_key, $s_folder_key='ImageUploadOriginals')
	{
		if (!isset($_FILES[$s_key]) or !is_array($_FILES[$s_key]) or !is_uploaded_file($_FILES[$s_key]['tmp_name']) or $_FILES[$s_key]['error'] != 0) die('No file uploaded');

		$this->o_settings = &$o_settings;
		$this->s_upload_dir = $this->o_settings->GetFolder($s_folder_key);

		$this->s_type = $_FILES[$s_key]['type'];
		$this->s_file = $_FILES[$s_key]['name'];
		$this->s_temp_file = $_FILES[$s_key]['tmp_name'];
		$this->i_size = (int)$_FILES[$s_key]['size'];
	}

	/**
	* @return string
	* @desc Save the uploaded file to a permanent location
	*/
	function Save()
	{
		### Generate filename from original filename

		# Remove path
		$s_file = strtolower(basename($this->s_file));

		# Split filename and extension
		$i_dot_pos = strrpos($s_file, '.');
		$this->s_file_name = ($i_dot_pos >= 1) ? substr($s_file, 0, $i_dot_pos) : '';
		$this->s_file_ext = ($i_dot_pos >= 1) ? substr($s_file, $i_dot_pos+1) : '';

		# Remove unwanted characters
		$this->s_file_name = preg_replace('/[^a-z0-9]/', '', $this->s_file_name);
		$this->s_file_ext = preg_replace('/[^a-z0-9]/', '', $this->s_file_ext);
		if ($this->s_file_ext) $this->s_file_ext = '.' . $this->s_file_ext;

		# If on localhost, prefix "localhost-" so that accidently FTP'd files
		# won't overwrite those uploaded to public box
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1') $this->s_file_name = 'localhost-' . $this->s_file_name;

		# Save file with a unique filename, preferring brevity
		$s_unique_name = $this->s_upload_dir . $this->s_file_name . $this->s_file_ext;
		if (!file_exists($s_unique_name))
		{
			move_uploaded_file($this->s_temp_file, $s_unique_name);
		}
		else
		{
			$s_user_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
			$s_unique_name = $this->s_upload_dir . $this->s_file_name . str_replace('.', '', $s_user_ip) . $this->s_file_ext;
			if (!file_exists($s_unique_name))
			{
				move_uploaded_file($this->s_temp_file, $s_unique_name);
				$this->s_file_name = $this->s_file_name . str_replace('.', '', $s_user_ip);
			}
			else
			{
				# Don't check existence because this *will* be unique
				$this->s_file_name = $this->s_file_name . str_replace('.', '', $s_user_ip) . gmdate('U');
				$s_unique_name = $this->s_upload_dir . $this->s_file_name . $this->s_file_ext;
				move_uploaded_file($this->s_temp_file, $s_unique_name);
			}
		}

		return $s_unique_name;
	}

	/**
	 * Generates a thumbnail-sized image (max 150px x 150px) from the uploaded file
	 *
	 * @return string
	 */
	public function GenerateImageThumbnail()
	{
		return $this->GenerateResizedImage(150, 150, $this->o_settings->GetFolder('ThumbnailUpload'));
	}

	/**
	 * Generates an image suitable for a web page (max 600px x 600px) from the uploaded file
	 *
	 * @return string
	 */
	public function GenerateImageWeb()
	{
		return $this->GenerateResizedImage(550, 550, $this->o_settings->GetFolder('ImageUpload'));
	}

	/**
	 * Generates a smaller version of an image if it is larger than a given size, and returns the filename
	 *
	 * @return string
	 */
	private function GenerateResizedImage($i_max_width, $i_max_height, $s_folder_to_save)
	{
		# check there's a filename
		if (isset($this->s_file_name) and strlen($this->s_file_name) > 0)
		{
			$s_thumb_file = $s_folder_to_save . $this->s_file_name . $this->s_file_ext;

			# get current and new size
			$a_size = getimagesize($this->s_upload_dir . $this->s_file_name . $this->s_file_ext);
			$a_new_size = $this->GetImageThumbnailSize($a_size[0], $a_size[1], $i_max_width, $i_max_height);

			# deal with file types separately because PHP has dedicated functions
			if ($this->s_file_ext == '.jpg' or $this->s_file_ext == '.jpeg')
			{
				# get image to resize
				$o_src_image = imagecreatefromjpeg($this->s_upload_dir . $this->s_file_name . $this->s_file_ext);
				if ($o_src_image)
				{
					# create new image and copy resized image into it
					$o_thumb = imagecreatetruecolor($a_new_size[0], $a_new_size[1]);
					imagecopyresampled($o_thumb, $o_src_image, 0, 0, 0, 0, $a_new_size[0], $a_new_size[1], $a_size[0], $a_size[1]);

					# save the new image and return
					if ($o_thumb)
					{
						imagejpeg($o_thumb, $s_thumb_file);
						return $s_thumb_file;
					}
				}
			}
			elseif ($this->s_file_ext == '.gif')
			{
				# get image to resize
				$o_src_image = imagecreatefromgif($this->s_upload_dir . $this->s_file_name . $this->s_file_ext);
				if ($o_src_image)
				{
					# create new image and copy resized image into it
					$o_thumb = imagecreatetruecolor($a_new_size[0], $a_new_size[1]);
					imagecopyresampled($o_thumb, $o_src_image, 0, 0, 0, 0, $a_new_size[0], $a_new_size[1], $a_size[0], $a_size[1]);

					# save the new image and return
					if ($o_thumb)
					{
						imagegif($o_thumb, $s_thumb_file);
						return $s_thumb_file;
					}
				}
			}
			elseif ($this->s_file_ext == '.png')
			{
				# get image to resize
				$o_src_image = imagecreatefrompng($this->s_upload_dir . $this->s_file_name . $this->s_file_ext);
				if ($o_src_image)
				{
					# create new image and copy resized image into it
					$o_thumb = imagecreatetruecolor($a_new_size[0], $a_new_size[1]);
					imagecopyresampled($o_thumb, $o_src_image, 0, 0, 0, 0, $a_new_size[0], $a_new_size[1], $a_size[0], $a_size[1]);

					# save the new image and return
					if ($o_thumb)
					{
						imagepng($o_thumb, $s_thumb_file);
						return $s_thumb_file;
					}
				}
			}
		}
	}

	/**
	* @return int[]
	* @param int $i_image_width
	* @param int $i_image_height
	* @param int $i_max_thumb_width
	* @param int $i_max_thumb_height
	* @desc Calculate the size of a thumbnail image from the size of an existing image and the maximum allowed size for the thumbnail
	*/
	private function GetImageThumbnailSize($i_image_width, $i_image_height, $i_max_thumb_width, $i_max_thumb_height)
	{
		if ($i_image_width <= $i_max_thumb_width and $i_image_height <= $i_max_thumb_height)
		{
			return array($i_image_width, $i_image_height);
		}
		else
		{
		(int)$i_thumb_w = ($i_image_width <= $i_image_height) ? round(($i_image_width * $i_max_thumb_height)/$i_image_height) : $i_max_thumb_width;
		(int)$i_thumb_h = ($i_image_width > $i_image_height) ? round(($i_image_height * $i_max_thumb_width)/$i_image_width) : $i_max_thumb_height;

		return array($i_thumb_w, $i_thumb_h);
		}
	}
}
?>
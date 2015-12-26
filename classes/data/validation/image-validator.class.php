<?php
require_once('data/validation/data-validator.class.php');

class ImageValidator extends DataValidator
{
	var $i_max_width;
	var $i_max_height;
	var $i_max_bytes;
	
	/**
	* @return ImageValidator
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_max_width Maximum width of image in pixels
	* @param int $i_max_height Maximum height of image in pixels
	* @param int $i_max_bytes Maximum size of image in bytes
	* @param int $i_mode
	* @desc Validate an uploaded image
	*/
	function ImageValidator($a_keys, $s_message, $i_max_width=500, $i_max_height=500, $i_max_bytes=50000, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->i_max_width = (int)$i_max_width;
		$this->i_max_height = (int)$i_max_height;
		$this->i_max_bytes = (int)$i_max_bytes;
		$this->a_data = &$_FILES;
	}
	/**
	* @return bool
	* @param array $a_input
	* @param string[] field names $a_keys
	* @desc Test whether an uploaded image meets the criteria
	*/
	function Test($a_input, $a_keys) 
	{
		$b_valid = true;
		
		# check file size not too small or too big
		$i_size = (int)$a_input['size'];
		if ($i_size <= 0 or $i_size > $this->i_max_bytes) $b_valid = false;
		
		# check file ext for image
		if ($b_valid)
		{
			$s_name = basename($a_input['name']);
			$i_dot_pos = strrpos($s_name, '.');
			if ($i_dot_pos <= 0) 
			{
				$b_valid = false;
			}
			else
			{
				$a_image_ext = array('jpg', 'jpeg', 'gif', 'png');
				$s_file_ext = substr($s_name, $i_dot_pos+1);
				if (!in_array($s_file_ext, $a_image_ext, true)) $b_valid = false;
			}
		}
		
		# check mime type for image
		if ($b_valid)
		{
			$a_mime = explode('/', $a_input['type']);
			if ($a_mime[0] != 'image') $b_valid = false;
		}
		
		# check image size
		if ($b_valid)
		{
			$a_image_size = getimagesize($a_input['tmp_name']);
			if (!is_array($a_image_size)) $b_valid = false;
			if ($a_image_size[0] <= 0 or $a_image_size[0] > $this->i_max_width) $b_valid = false;
			if ($a_image_size[1] <= 0 or $a_image_size[1] > $this->i_max_height) $b_valid = false;
		}
		
		return $b_valid;
	}
}
?>
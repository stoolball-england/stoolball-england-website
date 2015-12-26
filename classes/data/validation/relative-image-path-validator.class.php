<?php
require_once('data/validation/data-validator.class.php');

class RelativeImagePathValidator extends DataValidator
{
	var $o_settings;
	
	/**
	* @return RelativeImagePathValidator
	* @param SiteSettings $o_settings
	* @param array $a_keys
	* @param string $s_message
	* @param int $i_mode
	* @desc Validate a path to an image uploaded to the default image folder
	*/
	function RelativeImagePathValidator(SiteSettings $o_settings, $a_keys, $s_message, $i_mode=null)
	{
		parent::DataValidator($a_keys, $s_message, $i_mode);
		$this->o_settings = &$o_settings;
	}
	/**
	* @return bool
	* @param string $s_input
	* @param string[] field names $a_keys
	* @desc Test whether a given string is a valid image path
	*/
	function Test($s_input, $a_keys) 
	{
		if ($s_input)
		{
			# check format of path
			$b_valid = (bool)preg_match('/^[a-z0-9\/\-_]+\.(jpg|gif|png)$/', $s_input);
			
			# file must exist
			$s_full_path = $this->o_settings->GetFolder('ImagesServer') . $s_input;
			if (!file_exists($s_full_path) || is_dir($s_full_path)) 
			{
				$b_valid = false;
				$this->SetMessage('The image you chose was not found');
			}
	
			# return result
			return $b_valid;
		}
		else return true; # empty string is valid
	}
}
?>
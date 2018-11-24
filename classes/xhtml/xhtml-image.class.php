<?php
require_once('xhtml-element.class.php');

class XhtmlImage extends XhtmlElement 
{
	# declare class-wide properties
	private $o_settings;

	/**
	 * Create an image
	 * @param SiteSettings $settings
	 * @param string $s_path
	 * @return void
	 */
	public function __construct($o_settings=null, $s_path='')
	{
		parent::__construct('img');
		$this->o_settings = $o_settings; // $settings must remain nullable, to support using external images which don't need local paths
		$this->SetEmpty(true); # <img /> is an empty element
		$this->AddAttribute('alt', ''); # default to empty alt text

		if ($s_path)
		{
			# check image exists
			if (file_exists($o_settings->GetFolder('ImagesServer') . $s_path) and !is_dir($o_settings->GetFolder('ImagesServer') . $s_path))
			{
				# get its size
				$a_image_details = getimagesize($o_settings->GetFolder('ImagesServer') . $s_path);
				if (is_array($a_image_details))
				{
					$this->SetWidth($a_image_details[0]);
					# Don't set height, so that photos can resize down to the width of a small screen
					# $this->SetHeight($a_image_details[1]);
				}

				# set other properties
				$this->AddAttribute('src', $o_settings->GetFolder('Images') . $s_path);
			}
			else
			{
				return null; #image doesn't exist
			}
		}
		else
		{
			return null; # insufficient data
		}
	}

	# getters and setters for properties
	function SetUrl($s_text)
	{
		# this function should re-check existence etc...
		$this->AddAttribute('src', $s_text);
	}

	/**
	 * @return string
	 * @param bool $b_trim_image_root
	 * @desc Get the client-side virtual URL of the image
	 */
	function GetUrl($b_trim_image_root=false)
	{
		$s_url = $this->GetAttribute('src');
		if ($b_trim_image_root)
		{
			$s_root = $this->o_settings->GetFolder('Images');
			$i_root_len = strlen($s_root);
			if (substr($s_url, 0, $i_root_len) == $s_root) $s_url = substr($s_url, $i_root_len);
		}

		return $s_url;
	}

	/**
	 * @return void
	 * @param string $s_text
	 * @desc Set the description of the image, which will be used at the alternative text
	 */
	function SetDescription($s_text)
	{
		$this->AddAttribute('alt', $s_text);
	}

	/**
	 * @return string
	 * @desc Gets the description of the image, which is used as the alternative text
	 */
	function GetDescription()
	{
		return $this->GetAttribute('alt');
	}

	function SetWidth($i_number)
	{
		$this->AddAttribute('width', (int)$i_number);
	}

	/**
	 * @return int
	 * @desc Get the width of the image in pixels
	 */
	function GetWidth()
	{
		return $this->GetAttribute('width');
	}

	function SetHeight($i_number)
	{
		$this->AddAttribute('height', (int)$i_number);
	}

	function GetHeight()
	{
		return $this->GetAttribute('height');
	}

	# more detailed methods to write properties
	function __toString()
	{
		$s_tag = parent::__toString();

		if ($this->GetUrl())
		{
			return $s_tag;
		}
		else
		{
			return ''; # no url, no image!
		}
	}
}
?>
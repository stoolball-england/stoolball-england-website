<?php
require_once('xhtml-element.class.php');
require_once('http/has-short-url.interface.php');

class XhtmlImage extends XhtmlElement implements IHasShortUrl
{
	# declare class-wide properties
	private $o_settings;
	private $i_image_id;
	private $s_navigate_url;
	private $s_navigate_title;
	private $o_thumb;
	private $s_longdesc;
	private $a_galleries;
	private $s_original_url;
	private $s_short_url;

	/**
	 * User who checked that the image is appropriate
	 *
	 * @var User
	 */
	private $checked_by;
	private $i_checked_date;

	/**
	 * User who uploaded the image
	 *
	 * @var User
	 */
	private $uploaded_by;
	private $i_uploaded_date;
	private $b_new_upload = false;


	/**
	 * Create an image
	 * @param SiteSettings $settings
	 * @param string $s_path
	 * @return void
	 */
	public function __construct($o_settings=null, $s_path='')
	{
		parent::XhtmlElement('img');
		$this->o_settings = $o_settings; // $settings must remain nullable, to support using external images which don't need local paths
		$this->SetEmpty(true); # <img /> is an empty element
		$this->a_galleries = array();
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
	 * Sets the URL of the original image, before resizing for the web
	 *
	 * @param string $s_text
	 */
	public function SetOriginalUrl($s_text)
	{
		# this function should re-check existence etc...
		$this->s_original_url = $s_text;
	}

	/**
	 * @return string
	 * @param bool $b_trim_image_root
	 * @desc Get the client-side virtual URL of the image
	 */
	function GetOriginalUrl($b_trim_image_root=false)
	{
		$s_url = $this->s_original_url;
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
		if (is_object($this->o_thumb)) $this->o_thumb->SetDescription($s_text);
	}

	/**
	 * @return string
	 * @desc Gets the description of the image, which is used as the alternative text
	 */
	function GetDescription()
	{
		return $this->GetAttribute('alt');
	}

	/**
	 * @return void
	 * @param string $s_text
	 * @desc Sets the detailed description of the image
	 */
	function SetLongDescription($s_text)
	{
		$this->s_longdesc = $s_text;
		if (is_object($this->o_thumb)) $this->o_thumb->SetLongDescription($s_text);
	}

	/**
	 * @return string
	 * @desc Gets the detailed description of the image
	 */
	function GetLongDescription()
	{
		return $this->s_longdesc;
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

	/**
	 * @return void
	 * @param int $i_id
	 * @desc Sets the unique database identifier for the image
	 */
	function SetId($i_id)
	{
		$this->i_image_id = $i_id;
	}

	/**
	 * @return int
	 * @desc Gets the unique database identifier for the image
	 */
	function GetId()
	{
		return $this->i_image_id;
	}

	/**
	 * @return void
	 * @param string $s_text
	 * @desc Sets the URL to link the image to
	 */
	function SetNavigateUrl($s_text)
	{
		$this->s_navigate_url = $s_text;
	}

	/**
	 * @return string
	 * @desc Gets the URL to link the image to
	 */
	function GetNavigateUrl()
	{
		return $this->s_navigate_url;
	}

	/**
	 * @return void
	 * @param string $s_text
	 * @desc Sets the popup text to be displayed when when hovering over the linked image
	 */
	function SetNavigateTitle($s_text)
	{
		$this->s_navigate_title = $s_text;
	}

	/**
	 * @return string
	 * @desc Gets the popup text to be displayed when when hovering over the linked image
	 */
	function GetNavigateTitle()
	{
		return $this->s_navigate_title;
	}

	/**
	 * @return void
	 * @param string $s_url
	 * @desc Sets the path to a separate image to be used as a thumbnail
	 */
	function SetThumbnailUrl($s_url)
	{
		if (is_string($s_url) and strlen($s_url) > 0)
		{
			$this->o_thumb = new XhtmlImage($this->o_settings, $s_url);
			$this->o_thumb->SetDescription($this->GetDescription());
			$this->o_thumb->SetLongDescription($this->GetLongDescription());
		}
	}

	/**
	 * @return string
	 * @param bool $b_trim_image_root
	 * @desc Gets the client-side virtual URL of the thumbnail image
	 */
	function GetThumbnailUrl($b_trim_image_root=false)
	{
		$o_thumb = $this->GetThumbnail();
		return $o_thumb->GetUrl($b_trim_image_root);
	}

	function SetThumbnail(XhtmlImage $o_image)
	{
		$this->o_thumb = &$o_image;
	}

	/**
	 * @return XhtmlImage
	 * @desc Get image to use as a thumbnail representation of the full image
	 */
	function GetThumbnail()
	{
		if (!isset($this->o_thumb) or !is_object($this->o_thumb)) $this->o_thumb = &$this;
		return $this->o_thumb;
	}

	/**
	 * @return void
	 * @param MediaGallery $o_gallery
	 * @desc Add a MediaGalleryin which this image is included
	 */
	function AddGallery(MediaGallery $o_gallery)
	{
		$this->a_galleries[] = $o_gallery;
	}

	function GetGalleries()
	{
		return $this->a_galleries;
	}

	# more detailed methods to write properties
	function __toString()
	{
		$s_tag = parent::__toString();

		if ($this->GetUrl())
		{
			if ($this->GetNavigateUrl())
			{
				$s_tag = ' class="graphic">' . $s_tag . '</a>';
				if ($this->GetNavigateTitle()) $s_tag = ' title="' . $this->GetNavigateTitle() . '"' . $s_tag;
				$s_tag = '<a href="' . $this->GetNavigateUrl() . '"' . $s_tag;
			}

			return $s_tag;
		}
		else
		{
			return ''; # no url, no image!
		}
	}

	/**
	 * Checks whether the image has been checked as appropriate for the website
	 *
	 */
	public function IsChecked()
	{
		return $this->i_checked_date > 0 and !is_null($this->checked_by);
	}

	/**
	 * Sets the identity of the person who checked the image was appropriate for the website
	 *
	 * @param User $person
	 */
	public function SetCheckedBy(User $person)
	{
		$this->checked_by = $person;
	}

	/**
	 * Gets the identity of the person who checked the image was appropriate for the website
	 *
	 * @return User
	 */
	public function GetCheckedBy()
	{
		return $this->checked_by;
	}

	/**
	 * Sets the date and time the image was checked as appropriate for the website
	 *
	 * @param int $i_timestamp
	 */
	public function SetCheckedDate($i_timestamp)
	{
		$this->i_checked_date = (int)$i_timestamp;
	}

	/**
	 * Gets the date and time the image was checked as appropriate for the website
	 *
	 * @return int
	 */
	public function GetCheckedDate()
	{
		return $this->i_checked_date;
	}

	/**
	 * Sets whether the image has just been uploaded
	 *
	 * @param bool $b_new
	 */
	public function SetIsNewUpload($b_new)
	{
		$this->b_new_upload = (bool)$b_new;
	}

	/**
	 * Gets whether the image has just been uploaded
	 *
	 * @return bool
	 */
	public function GetIsNewUpload()
	{
		return $this->b_new_upload;
	}

	/**
	 * Sets the identity of the person who uploaded the image
	 *
	 * @param User $person
	 */
	public function SetUploadedBy(User $person)
	{
		$this->uploaded_by = $person;
	}

	/**
	 * Gets the identity of the person who uploaded the image
	 *
	 * @return User
	 */
	public function GetUploadedBy()
	{
		return $this->uploaded_by;
	}

	/**
	 * Sets the date and time the image was uploaded
	 *
	 * @param int $i_timestamp
	 */
	public function SetUploadedDate($i_timestamp)
	{
		$this->i_uploaded_date = (int)$i_timestamp;
	}

	/**
	 * Gets the date and time the image was uploaded
	 *
	 * @return int
	 */
	public function GetUploadedDate()
	{
		return $this->i_uploaded_date;
	}

	/**
	 * Suggest a suitable short URL based on the properties of the object
	 *
	 * @param int $i_preference
	 */
	public function SuggestShortUrl($i_preference=1) { throw new Exception('Image short URLs are based on their containing MediaGallery. Use MediaGallery::SuggestSortUrl instead.'); }

	/**
	 * Sets the short URL for an object
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = (string)$s_url; }

	/**
	 * Gets the short URL for an object
	 *
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * Gets the real URL for an object
	 *
	 */
	public function GetRealUrl() { return $this->GetUrl(); }

	/**
	 * Gets the format to use for an image's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('MediaGalleryLink'), 'short_url', array('gallery_id', 'item_id'), array('GetId'),
		array(
		'{0}' => $settings->GetFolder('Play') . $settings->GetUrl('ImagePage')
		));
	}

	/**
	 * Gets the format to use for an image's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return XhtmlImage::GetShortUrlFormatForType($this->o_settings);
	}
}
?>
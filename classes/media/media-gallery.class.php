<?php
require_once('http/has-short-url.interface.php');
require_once('http/short-url.class.php');
require_once('media/gallery-item.class.php');

class MediaGallery implements IHasShortUrl
{
	private $o_settings;
	private $i_id;
	private $s_title;
	private $s_desc;
	private $images;
	private $related_items;
	private $added_by;
	private $cover_image;
	private $s_short_url;
	private $current_index = 0;

	/**
	 * Creates a MediaGallery
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->o_settings = $settings;
		$this->images = new Collection(null, 'GalleryItem');
		$this->related_items = new Collection(null, 'IHasMedia');
	}

	/**
	 * Gets a unique identifier for this gallery
	 *
	 * @param int $i_id
	 */
	public function SetId($i_id)
	{
		if (is_numeric($i_id)) $this->i_id = (int)$i_id;
	}

	/**
	 * @return int
	 * @desc Gets the unique database identifier of the MediaGallery
	 */
	function GetId()
	{
		return $this->i_id;
	}

	/**
	 * @return void
	 * @param string $s_title
	 * @desc Sets the title of the media gallery
	 */
	function SetTitle($s_title)
	{
		$this->s_title = (string)$s_title;
	}

	/**
	 * @return string
	 * @desc Gets the title of the media gallery
	 */
	function GetTitle()
	{
		return $this->s_title;
	}

	/**
	 * @return string
	 * @desc Gets the title of the media gallery
	 */
	public function __toString()
	{
		return $this->GetTitle();
	}

	/**
	 * @return void
	 * @param string $s_desc
	 * @desc Sets the description of the media gallery
	 */
	function SetDescription($s_desc)
	{
		$this->s_desc = (string)$s_desc;
	}

	/**
	 * @return string
	 * @desc Gets the description of the media gallery
	 */
	function GetDescription()
	{
		return $this->s_desc;
	}

	/**
	 * Gets a collection of IHasMedia items which are related to this media gallery
	 *
	 * @return Collection
	 */
	public function RelatedItems()
	{
		return $this->related_items;
	}

	/**
	 * @return Collection
	 * @desc Gets the images in the media gallery
	 */
	public function Images()
	{
		return $this->images;
	}

	/**
	 * Sets the short URL for a team
	 *
	 * @param string $s_url
	 */
	public function SetShortUrl($s_url) { $this->s_short_url = trim($s_url); }

	/**
	 * Gets the short URL for a gallery
	 *
	 * @return string
	 */
	public function GetShortUrl() { return $this->s_short_url; }

	/**
	 * Gets the real URL for a gallery
	 *
	 * @return string
	 */
	public function GetRealUrl()
	{
		$s_url = str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('MediaGalleryPage'));
		if (isset($_GET['tempus'])) $s_url .= '&amp;tempus=fixit';
		return $s_url;
	}


	/**
	 * Gets the format to use for a gallery's short URLs
	 *
	 * @param SiteSettings
	 * @return ShortUrlFormat
	 */
	public static function GetShortUrlFormatForType(SiteSettings $settings)
	{
		return new ShortUrlFormat($settings->GetTable('MediaGallery'), 'short_url', array('gallery_id'), array('GetId'),
		array(

		# Media galleries' main home in Play
		'{0}' => $settings->GetFolder('Play') . $settings->GetUrl('MediaGalleryPage'),
		'{0}addphoto' => $settings->GetUrl('MediaGalleryAddPhoto'),
		'{0}addphotos' => $settings->GetUrl('MediaGalleryAddPhotos'),
		'{0}edit' => $settings->GetUrl('MediaGalleryEdit'),
		'{0}delete' => $settings->GetUrl('MediaGalleryDelete')
		));
	}

	/**
	 * Gets the format to use for a gallery's short URLs
	 *
	 * @return ShortUrlFormat
	 */
	public function GetShortUrlFormat()
	{
		return MediaGallery::GetShortUrlFormatForType($this->o_settings);
	}

	/**
	 * Suggests a short URL to use to view the gallery
	 *
	 * @param int $i_preference
	 * @return string
	 */
	public function SuggestShortUrl($i_preference=1)
	{
		$s_url = strtolower(html_entity_decode($this->GetTitle()));

		# Remove remaining entities and punctuation
		$s_url = preg_replace('/&#[0-9]+;/i', '', $s_url);
		$s_url = preg_replace('/[^a-z0-9 ]/i', '', $s_url);

		# Remove noise words
		$s_url = preg_replace(array('/\bstoolball\b/i', '/\bclub\b/i', '/\bladies\b/i', '/\bmixed\b/i', '/\bsports\b/i', '/\bthe\b/i', '/\band\b/i'), '', $s_url);

		# Apply preference
		if ($i_preference > 1)
		{
			$s_url = ShortUrl::SuggestShortUrl($s_url, $i_preference, 1, 'album');
		}

		# Remove spaces
		$s_url = str_replace(' ', '', $s_url);

		return $s_url;
	}

	/**
	 * @return string
	 * @desc Gets the URL for the media gallery
	 */
	public function GetNavigateUrl()
	{
		$s_url = '';
		if ($this->GetShortUrl())
		{
			$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl();
		}
		else
		{
			$s_url = $this->GetRealUrl();
		}

		return $s_url;
	}

	/**
	 * @return string
	 * @desc Gets the URL to edit the media gallery 
	 */
	public function GetEditGalleryUrl()
	{
		if ($this->GetShortUrl())
		{
			return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . 'edit';
		}
		else
		{
			return str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('MediaGalleryEdit'));
		}
	}

	/**
	 * @return string
	 * @param bool $multiple_images
	 * @desc Gets the URL for adding images to the gallery
	 */
	public function GetAddImagesNavigateUrl($multiple_images=true)
	{
		$s_url = '';
		$multiple_images = $multiple_images ? 's' : '';
		if ($this->GetShortUrl())
		{
			$s_url = $this->o_settings->GetClientRoot() . $this->GetShortUrl() . 'addphoto' . $multiple_images;
		}
		else
		{
			$s_url = str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('MediaGalleryAddPhoto' . $multiple_images));
		}

		return $s_url;
	}

	/**
	 * @return string
	 * @desc Gets the URL for deleting the gallery
	 */
	public function GetDeleteNavigateUrl()
	{
		if ($this->GetShortUrl())
		{
			return $this->o_settings->GetClientRoot() . $this->GetShortUrl() . 'delete';
		}
		else
		{
			return str_replace('{0}', $this->GetId(), $this->o_settings->GetUrl('MediaGalleryDelete'));
		}
	}

	/**
	 * Sets who added the gallery to the website
	 *
	 * @param User $person
	 */
	public function SetAddedBy(User $person) { $this->added_by = $person; }

	/**
	 * Gets who added the gallery to the website
	 *
	 * @return User
	 */
	public function GetAddedBy() { return $this->added_by; }

	/**
	 * Sets the image used to represent this album in album listings
	 *
	 * @param XhtmlImage $image
	 */
	public function SetCoverImage(XhtmlImage $image) { $this->cover_image = $image; }

	/**
	 * Gets the image used to represent this album in album listings
	 *
	 * @return XhtmlImage
	 */
	public function GetCoverImage() { return $this->cover_image; }

	/**
	 * Gets whether this album has an image to display in album listings
	 *
	 * @return bool
	 */
	public function HasCoverImage() { return $this->cover_image instanceof XhtmlImage; }

	/**
	 * Sets the current item in the gallery by providing its id
	 * @param int $item_id
	 * @return void
	 */
	public function SetCurrentImageById($item_id)
	{
		$this->current_index = $this->Images()->GetItemIndexByProperty('GetItemId', (int)$item_id);
	}

	/**
	 * Gets the image at the current index
	 * @return GalleryItem
	 */
	public function GetCurrentImage()
	{
		if (is_null($this->current_index)) return null;
		return $this->Images()->GetByIndex($this->current_index);
	}

	/**
	 * Gets the previous image in the gallery
	 * @return GalleryItem
	 */
	public function GetPreviousImage()
	{
		if (is_null($this->current_index)) return null;
		return $this->Images()->GetByIndex($this->current_index-1);
	}

	/**
	 * Gets the next image in the gallery
	 * @return GalleryItem
	 */
	public function GetNextImage()
	{
		if (is_null($this->current_index)) return null;
		return $this->Images()->GetByIndex($this->current_index+1);
	}

	/**
	 * Gets current image's position in gallery, eg 2 of 5
	 * @return int or false
	 */
	public function GetCurrentImagePosition()
	{
		if (is_null($this->current_index)) return false;
		return $this->current_index+1;
	}
}
?>
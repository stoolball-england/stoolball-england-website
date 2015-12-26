<?php
require_once('xhtml/placeholder.class.php');

class ImageControl extends XhtmlElement
{
	/**
	 * Gets the image to display
	 * @var XhtmlImage
	 */
	private $image;

	/**
	 * The gallery in which the image is being displayed
	 * @var MediaGallery
	 */
	private $gallery;

	/**
	 * Creates a new ImageControl
	 * @param XhtmlImage $image
	 * @param MediaGallery $gallery
	 * @return void
	 */
	public function __construct(XhtmlImage $image, $gallery = null)
	{
		$this->image = $image;
		$this->gallery = $gallery;

		# set up container
		parent::XhtmlElement('div');
	}

	/**
	 * Gets the image in customised versions of this control
	 *
	 * @return XhtmlImage
	 */
	protected function GetImage()
	{
		return $this->image;
	}

	function OnPreRender()
	{
		$this->AddCssClass(get_class($this));

		$b_longdesc = (bool)$this->image->GetLongDescription();

		$imagebox = new XhtmlElement('div');
		$imagebox->SetCssClass('photo');
		$this->AddControl($imagebox);

		if ($this->gallery instanceof MediaGallery)
		{
			require_once('media/media-gallery-navigation.class.php');
			$imagebox->AddControl(new MediaGalleryNavigation($this->gallery));

			$next_image = $this->gallery->GetNextImage();
			if ($next_image)
			{
				$this->image->SetNavigateUrl($next_image->GetUrl());
				$this->image->SetNavigateTitle('Click to see next photo');
			}
			else
			{
				$first_image = $this->gallery->Images()->GetFirst();
				$this->image->SetNavigateUrl($first_image->GetUrl());
				$this->image->SetNavigateTitle('Click to see first photo');
			}
		}
		$imagebox->AddControl($this->image);

		$caption = new XhtmlElement('p', Html::Encode($this->image->GetDescription()));
		$caption_outer1 = new XhtmlElement('div', $caption);
		$caption_outer1->SetCssClass('photoCaption');
		$this->AddControl($caption_outer1);

		if ($b_longdesc)
		{
			$desc = XhtmlMarkup::ApplyCharacterEntities(Html::Encode($this->image->GetLongDescription()));
			$desc = XhtmlMarkup::ApplyParagraphs($desc);
			$desc = XhtmlMarkup::ApplyLinks($desc);
			$desc = XhtmlMarkup::ApplySimpleTags($desc);
			$o_desc = new XhtmlElement('div', $desc);
			$o_desc->SetXhtmlId('longDesc');
			$this->AddControl($o_desc);
		}
	}
}
?>
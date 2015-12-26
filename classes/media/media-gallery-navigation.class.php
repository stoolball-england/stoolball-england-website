<?php
require_once('xhtml/xhtml-element.class.php');

/**
 * Control to enable navigation through a media gallery
 *
 */
class MediaGalleryNavigation extends XhtmlElement
{
	/**
	 * The gallery to navigate
	 *
	 * @var MediaGallery
	 */
	private $gallery;

	/**
	 * Creates a MediaGalleryNavigationControl
	 *
	 * @param MediaGallery $gallery
	 */
	public function __construct(MediaGallery $gallery)
	{
		$this->gallery = $gallery;
		parent::XhtmlElement('div');
	}

	protected function OnPreRender()
	{
		$this->AddCssClass('paging');
		$count = $this->gallery->Images()->GetCount();

		$this->AddControl('<p>In album: <a href="' . Html::Encode($this->gallery->GetNavigateUrl()) . ' ">' . Html::Encode($this->gallery->GetTitle()) . '</a></p>');
		if ($count > 1)
		{
			$this->AddControl('<p class="pageContext">');
			$previous_image = $this->gallery->GetPreviousImage();
			$next_image = $this->gallery->GetNextImage();
			if ($previous_image)
			{
				$this->AddControl('<a href="/' . Html::Encode($previous_image->GetUrl()) . '">&lt; Prev</a>');
			}
			if ($next_image)
			{
				$this->AddControl(' <a href="/' . Html::Encode($next_image->GetUrl()) . '">Next &gt;</a>');
			}
			$this->AddControl('</p>');
		}
		$this->AddControl('<p class="pages">Photo ' . Html::Encode($this->gallery->GetCurrentImagePosition()) . ' of ' . Html::Encode($count) . '</p>');
	}
}
?>
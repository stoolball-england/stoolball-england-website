<?php
require_once('xhtml/xhtml-element.class.php');
require_once('markup/xhtml-markup.class.php');

class MediaGalleryListControl extends XhtmlElement
{
	private $a_galleries;
	private $b_list_albums_without_covers;

	function MediaGalleryListControl($a_galleries=null, $b_list_albums_without_covers=true)
	{
		parent::XhtmlElement('div');
		$this->a_galleries = (is_array($a_galleries)) ? $a_galleries : array();
		$this->b_list_albums_without_covers = $b_list_albums_without_covers;
	}

	function OnPreRender()
	{
		$this->AddCssClass('thumbnails');
		foreach ($this->a_galleries as $gallery)
		{
			if ($gallery instanceof MediaGallery)
			{
				/* @var $gallery MediaGallery */
				if (!$this->b_list_albums_without_covers and !$gallery->HasCoverImage()) continue;

				$container = new XhtmlElement('div');
				$container->SetCssClass('thumbnail');

				$url = $gallery->GetNavigateUrl();

				if ($gallery->HasCoverImage())
				{
					$thumb = $gallery->GetCoverImage()->GetThumbnail();

					$imagelink = new XhtmlAnchor('', $url);
					$imagelink->SetTitle('View this album');
					$imagelink->SetCssClass('photo thumbPhoto');
					$imagelink->AddControl($thumb);

					$container->AddControl($imagelink);
				}
				else
				{
					$nocover = new XhtmlElement('p', 'No cover photo.');
					$nocover->SetCssClass('photo thumbPhoto');
					$container->AddControl($nocover);
				}

				$captionlink = new XhtmlAnchor(Html::Encode($gallery->GetTitle()), $url);
				$caption = new XhtmlElement('p', $captionlink);
				$captionbox = new XhtmlElement('div', $caption);
				$captionbox->SetCssClass('photoCaption thumbCaption');
				$container->AddControl($captionbox);

				$this->AddControl($container);
			}
		}
	}
}
?>
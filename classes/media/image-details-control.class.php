<?php
require_once('media/image-control.class.php');

class ImageDetailsControl extends ImageControl
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $settings;

	public function __construct(SiteSettings $settings, XhtmlImage $image)
	{
		parent::__construct($image);
		$this->settings = $settings;
	}

	function OnPreRender()
	{

		# when uploaded
		$o_upload_note = new XhtmlElement('p');
		if (!is_null($this->GetImage()->GetUploadedBy())) 
		{
		    $o_upload_note->AddControl(Html::Encode('Uploaded by ' . $this->GetImage()->GetUploadedBy()->GetName() . ' at ' . Date::BritishDateAndTime($this->GetImage()->GetUploadedDate()) . '. '));
        }
		if ($this->GetImage()->GetOriginalUrl())
		{
			$o_upload_note->AddControl(new XhtmlAnchor('View original image', $this->settings->GetFolder('Images') . $this->GetImage()->GetOriginalUrl()));
			$o_upload_note->AddControl('.');
		}
		$this->AddControl($o_upload_note);

		parent::OnPreRender();

		# image is appropriate
		if ($this->GetImage()->IsChecked()) 
		{
		    $this->AddControl(new XhtmlElement('p', Html::Encode('Checked by ' . $this->GetImage()->GetCheckedBy()->GetName() . ' at ' . Date::BritishDateAndTime($this->GetImage()->GetCheckedDate()))));
        }
		$context = new XhtmlElement('div');
		$context->SetCssClass('imageContext');

		$thumb = new XhtmlElement('div', $this->GetImage()->GetThumbnail());
		$thumb->SetCssClass('photo thumbPhoto');
		$thumbbox = new XhtmlElement('div', $thumb);
		$thumbbox->SetCssClass('thumbnail');
		$context->AddControl($thumbbox);

		# display the galleries
		$galleries = $this->GetImage()->GetGalleries();
		if (count($galleries))
		{
			$context->AddControl(new XhtmlElement('h2', 'Albums'));
			$gallerylist = new XhtmlElement('ul');
			foreach ($galleries as $gallery)
			{
				$gallerylist->AddControl(new XhtmlElement('li', new XhtmlAnchor(Html::Encode($gallery->GetTitle()), $gallery->GetNavigateUrl())));
			}
			$context->AddControl($gallerylist);
		}
		$this->AddControl($context);
	}
}
?>
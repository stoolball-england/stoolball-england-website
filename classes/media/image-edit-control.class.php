<?php
require_once('data/data-edit-control.class.php');
require_once('data/related-id-editor.class.php');

class ImageEditControl extends DataEditControl
{
	/**
	 * Aggregated editor for selecting galleries
	 *
	 * @var RelatedIdEditor
	 */
	private $gallery_editor;
	private $i_max_upload_bytes = 2097152; # 2MB
	private $i_locked_to_gallery;

	public function __construct(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('XhtmlImage');
		parent::__construct($o_settings);
		$this->AddAttribute('enctype', 'multipart/form-data');

		# set up aggregated editors
		$this->gallery_editor = new RelatedIdEditor($o_settings, $this, 'Albums', 'Albums', array('Album title'), 'MediaGallery', true, 'GetId', 'SetId', 'SetTitle');
	}

	/**
	 * Force this control to add an image to a single gallery, instead of offering a choice
	 *
	 * @param int $gallery_id
	 */
	public function LockToGallery($gallery_id) { $this->i_locked_to_gallery = (int)$gallery_id; }

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$o_image = new XhtmlImage($this->GetSettings(), $_POST['url']);
		if (isset($_POST['item'])) $o_image->SetId($_POST['item']);
		$o_image->SetThumbnailUrl($_POST['thumbUrl']);
		$o_image->SetDescription($_POST['alternate']);
		$o_image->SetLongDescription($_POST['longDesc']);

		# Galleries - get from aggregated editor unless locked
		if ($this->i_locked_to_gallery)
		{
			$gallery = new MediaGallery($this->GetSettings());
			$gallery->SetId($this->i_locked_to_gallery);
			$o_image->AddGallery($gallery);
		}
		else
		{
			$a_galleries = $this->gallery_editor->DataObjects()->GetItems();
			foreach ($a_galleries as $o_gallery) $o_image->AddGallery($o_gallery);
		}

		# If file uploaded and validated, save it
		if (isset($_FILES['file']) and isset($_FILES['file']['name']) and strlen($_FILES['file']['name']) > 0 and $this->IsValid())
		{
			require_once('xhtml/forms/uploaded-file.class.php');
			$o_file = new UploadedFile($this->GetSettings(), 'file');
			$s_saved = $o_file->Save();

			if ($s_saved)
			{
				$s_saved = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $s_saved);
				$o_image->SetOriginalUrl($s_saved);
				$o_image->SetIsNewUpload(true);

				# Since a validated file is an image, generate a thumbnail
				$s_web_saved = $o_file->GenerateImageWeb();
				if ($s_web_saved)
				{
					$s_web_saved = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $s_web_saved);
					$o_image->SetUrl($s_web_saved);
				}

				$s_thumb_saved = $o_file->GenerateImageThumbnail();
				if ($s_thumb_saved)
				{
					$s_thumb_saved = str_replace($this->GetSettings()->GetFolder('ImagesServer'), '', $s_thumb_saved);
					$o_image->SetThumbnailUrl($s_thumb_saved);
				}
			}
		}

		$this->SetDataObject($o_image);
	}

	public function SetGalleries($a_galleries)
	{
		if (is_array($a_galleries)) $this->gallery_editor->SetPossibleDataObjects($a_galleries);
	}

	function CreateControls()
	{
		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');

		$o_image = $this->GetDataObject();
		if (is_null($o_image)) $o_image = new XhtmlImage($this->GetSettings(), '');
		/* @var $o_image XhtmlImage */

		# add image and thumbnail urls
		$i_prefix_len = strlen($this->GetSettings()->GetFolder('Images'));

		$o_url_box = new TextBox('url', substr($o_image->GetUrl(), $i_prefix_len));
		$o_url_box->SetMode(TextBoxMode::Hidden());
		$this->AddControl($o_url_box);

		$o_thumb_url_box = new TextBox('thumbUrl', substr($o_image->GetThumbnailUrl(), $i_prefix_len));
		$o_thumb_url_box->SetMode(TextBoxMode::Hidden());
		$this->AddControl($o_thumb_url_box);

		# add the thumbnail image
		if ($o_image->GetThumbnailUrl())
		{
			$o_thumb = new XhtmlElement('div', $o_image->GetThumbnail());
			$this->AddControl(new FormPart('Current photo', $o_thumb));
		}

		# add upload
		$o_file_box = new TextBox('file');
		$o_file_box->SetMode(TextBoxMode::File());
		$o_file_box->SetMaxLength($this->i_max_upload_bytes);
		$o_file = new FormPart('Select photo', $o_file_box);
		$this->AddControl($o_file);

		# add alternate text
		$o_alt_box = new TextBox('alternate', $o_image->GetDescription());
		$o_alt_box->SetMaxLength(200);
		$o_alt = new FormPart('Caption (up to 15 words)', $o_alt_box);
		$this->AddControl($o_alt);

		# add longdesc
		$o_longdesc_box = new TextBox('longDesc', $o_image->GetLongDescription());
		$o_longdesc_box->SetMode(TextBoxMode::MultiLine());
		$o_longdesc = new FormPart('Longer description (if appropriate)', $o_longdesc_box);
		$this->AddControl($o_longdesc);

		# add galleries
		if (!$this->i_locked_to_gallery)
		{
			if (!$this->IsPostback()) $this->gallery_editor->DataObjects()->SetItems($o_image->GetGalleries());
			$this->AddControl(new FormPart('Albums', $this->gallery_editor));
		}
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/words-validator.class.php');
		require_once('data/validation/relative-image-path-validator.class.php');
		require_once('data/validation/image-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');

		if ($_FILES['file']['name'] or !isset($_POST['item']) or !$_POST['item'])
		{
			$this->a_validators[] = new ImageValidator('file', 'Images must be in GIF, JPEG or PNG format and no more than 2500px x 2500px and 2MB in size', 2500, 2500, $this->i_max_upload_bytes);
		}
		$this->a_validators[] = new PlainTextValidator('alternate', 'Please use only letters, numbers and simple punctuation in the caption');
		$this->a_validators[] = new LengthValidator('alternate', 'Please make your caption shorter', 0, 200);
		$this->a_validators[] = new WordsValidator('alternate', 'Your caption should be 15 words or fewer', 0, 15);
		$this->a_validators[] = new PlainTextValidator('longDesc', 'Please use only letters, numbers and simple punctuation in the longer description');
		$this->a_validators[] = new LengthValidator('longDesc', 'Please make your longer description shorter', 0, 10000);
		$this->a_validators[] = new NumericValidator('gallery', 'The gallery identifier should be a number');

		$this->a_validators = array_merge($this->a_validators, $this->gallery_editor->GetValidators());
	}
}
?>
<?php
require_once('data/data-edit-control.class.php');
require_once('media/media-gallery.class.php');
require_once('stoolball/team.class.php');
require_once('data/related-id-editor.class.php');
require_once('stoolball/season-id-editor.class.php');
require_once('text/string-formatter.class.php');

class MediaGalleryEditControl extends DataEditControl
{
	/**
	 * Aggregated editor for selecting seasons
	 *
	 * @var SeasonIdEditor
	 */
	private $season_editor;

	/**
	 * Aggregated editor for selecting teams
	 *
	 * @var RelatedIdEditor
	 */
	private $teams_editor;
	private $i_locked_to_team;
	private $i_locked_to_season;
	private $user_is_admin = false;


	function MediaGalleryEditControl(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('MediaGallery');
		$this->season_editor = new SeasonIdEditor($o_settings, $this, 'Season');
		$this->teams_editor = new RelatedIdEditor($o_settings, $this, 'Team', 'Teams', array('Team name'), 'Team', true, 'GetId', 'SetId', 'SetName');
		parent::__construct($o_settings);
		
		# check permissions
		$this->user_is_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_ALBUMS);
	}

	/**
	 * Sets the seasons which might be related
	 *
	 * @param Season[] $a_seasons
	 */
	public function SetSeasons($a_seasons)
	{
		$this->season_editor->SetPossibleDataObjects($a_seasons);
	}

	public function SetTeams($a_teams)
	{
		$this->teams_editor->SetPossibleDataObjects($a_teams);
	}

	/**
	 * Force this control to associate an album with a single team, instead of offering a choice
	 *
	 * @param int $team_id
	 */
	public function LockToTeam($team_id) { $this->i_locked_to_team = (int)$team_id; }

	/**
	 * Force this control to associate an album with a single season, instead of offering a choice
	 *
	 * @param int $season_id
	 */
	public function LockToSeason($season_id) { $this->i_locked_to_season = (int)$season_id; }


	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$o_gallery = new MediaGallery($this->GetSettings());
		if (isset($_POST['item'])) $o_gallery->SetId($_POST['item']);
		$o_gallery->SetTitle($_POST['title']);
		$o_gallery->SetDescription($_POST['description']);
		$o_gallery->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);
		$s_key = $this->GetNamingPrefix() . 'Cover';
		if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$cover = new XhtmlImage($this->GetSettings(), '');
			$cover->SetId($_POST[$s_key]);
			$o_gallery->SetCoverImage($cover);
		}

		# Get data from aggregated editors
		if ($this->user_is_admin)
		{
			if (!$this->i_locked_to_season) foreach ($this->season_editor->DataObjects() as $season) $o_gallery->RelatedItems()->Add($season);
			if (!$this->i_locked_to_team) foreach ($this->teams_editor->DataObjects() as $o_team) $o_gallery->RelatedItems()->Add($o_team);
		}
		if ($this->i_locked_to_season)
		{
			$season = new Season($this->GetSettings());
			$season->SetId($this->i_locked_to_season);
			$o_gallery->RelatedItems()->Add($season);
		}
		if ($this->i_locked_to_team)
		{
			$team = new Team($this->GetSettings());
			$team->SetId($this->i_locked_to_team);
			$o_gallery->RelatedItems()->Add($team);
		}


		# Get images
		$i = 1;
		$s_key = $this->GetNamingPrefix() . 'Image' . $i;
		$a_images = array();
		$a_sort_images = array();
		$a_delete = array();
		while(isset($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$image = new XhtmlImage($this->GetSettings(), '');
			$image->SetId($_POST[$s_key]);
			$gallery_item = new GalleryItem($image,null);

			$s_key = $this->GetNamingPrefix() . 'ImageTitle' . $i;
			if (isset($_POST[$s_key])) $image->SetDescription($_POST[$s_key]);

			$s_key = $this->GetNamingPrefix() . 'ImageDesc' . $i;
			if (isset($_POST[$s_key])) $image->SetLongDescription($_POST[$s_key]);

			$s_key = $this->GetNamingPrefix() . 'Delete' . $i;
			if (isset($_POST[$s_key]) and $_POST[$s_key] == '1')
			{
				# Image is to be deleted
				$gallery_item->SetIsForDeletion(true);
			}

			$s_key = $this->GetNamingPrefix() . 'Sort' . $i;
			if (isset($_POST[$s_key]) and is_numeric($_POST[$s_key])) $a_sort_images[] = $_POST[$s_key];

			$a_images[] = $gallery_item;
			unset($image);

			# Loop to next image
			$i++;
			$s_key = $this->GetNamingPrefix() . 'Image' . $i;
		}

		# Sort images, then add to gallery
		if (count($a_images) == count($a_sort_images))
		{
			array_multisort($a_sort_images, SORT_NUMERIC, $a_images);
		}

		$o_gallery->Images()->SetItems($a_images);

		$this->SetDataObject($o_gallery);
	}

	function CreateControls()
	{
		/* @var $o_team Team */

		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');
		require_once('xhtml/forms/radio-button.class.php');
		require_once('xhtml/forms/xhtml-select.class.php');

        $this->AddCssClass('legacy-form');
		
		$o_gallery = $this->GetDataObject();
		/* @var $o_gallery MediaGallery */

		# add title
		$o_title_box = new TextBox('title', $o_gallery->GetTitle(), $this->IsValidSubmit());
		$o_title_box->SetMaxLength(100);
		$o_title = new FormPart('Title', $o_title_box);
		$o_title->SetIsRequired(true);
		$this->AddControl($o_title);

		# add description
		$o_desc_box = new TextBox('description', $o_gallery->GetDescription(), $this->IsValidSubmit());
		$o_desc_box->SetMode(TextBoxMode::MultiLine());
		$o_desc = new FormPart('Description', $o_desc_box);
		$this->AddControl($o_desc);

		# Add admin
		if ($this->user_is_admin)
		{
			if (!$this->IsPostback())
			{
				# add seasons, teams
				$a_seasons = array();
				$a_teams = array();

				foreach ($o_gallery->RelatedItems() as $item)
				{
					/* @var $item IHasMedia */
					switch ($item->GetContentType())
					{
                        case ContentType::STOOLBALL_SEASON:
							$a_seasons[] = $item;
							break;
						case ContentType::STOOLBALL_TEAM:
							$a_teams[] = $item;
							break;
					}
				}
				$this->season_editor->DataObjects()->SetItems($a_seasons);
				$this->teams_editor->DataObjects()->SetItems($a_teams);
			}

			if (!$this->i_locked_to_season) $this->AddControl(new FormPart('Seasons', $this->season_editor));
			if (!$this->i_locked_to_team) $this->AddControl(new FormPart('Teams', $this->teams_editor));
		}

		# Remember short URL
		$o_short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $o_gallery->GetShortUrl(), $this->IsValidSubmit());
		if ($this->user_is_admin)
		{
			$this->AddControl(new FormPart('Short URL', $o_short_url));
		}
		else
		{
			$o_short_url->SetMode(TextBoxMode::Hidden());
			$this->AddControl($o_short_url);
		}

		$i = 0;
		$cover_id = ($o_gallery->HasCoverImage()) ? $o_gallery->GetCoverImage()->GetId() : null;
		$total_images = $o_gallery->Images()->GetCount();
		foreach ($o_gallery->Images() as $gallery_item)
		{
			$image = $gallery_item->GetItem();
			/* @var $image XhtmlImage */

			$i++;

			$thumb = $image->GetThumbnail();
			$thumb_box = new XhtmlElement('div', $thumb);
			$thumb_box->SetCssClass('photo thumbPhoto');
			$cover = new XhtmlElement('div', $thumb_box);
			$cover->SetCssClass('cover');

			$sort = new XhtmlSelect($this->GetNamingPrefix() . 'Sort' . $i, 'Place in album: ');
			for ($j = 1; $j <= $total_images; $j++) $sort->AddControl(new XhtmlOption($j, $j, $i == $j));
			if ($this->IsPostback() and !$this->IsValidSubmit() and isset($_POST[$sort->GetXhtmlId()])) $sort->SelectOption($_POST[$sort->GetXhtmlId()]);

			$caption = new XhtmlElement('div', $sort);
			$captionbox = new XhtmlElement('div', $caption);
			$captionbox->SetCssClass('adminCaption thumbCaption'); # not photoCaption due to weird Gecko 1.8 bug where setting display to current values changes behaviour; adminCaption like photoCaption without display
			$cover->AddControl($captionbox);

			$remove = new CheckBox($this->GetNamingPrefix() . 'Delete' . $i, 'Remove this photo', 1, false, $this->IsValidSubmit());
			$caption->AddControl($remove);

			$edit = new XhtmlElement('fieldset', new XhtmlElement('legend', Html::Encode('Photo ' . $i . ': ' . StringFormatter::TrimToWord($image->GetDescription(), 10, true))));
			$edit->SetCssClass('mediaEdit');
			$edit->AddControl($cover);
			$this->AddControl($edit);

			# Add text edit fields
			$fields = new XhtmlElement('div');
			$fields->SetCssClass('mediaEditFields');

			$id = new TextBox($this->GetNamingPrefix() . 'Image' . $i, $image->GetId());
			$id->SetMode(TextBoxMode::Hidden());
			$fields->AddControl($id);

			$title = new TextBox($this->GetNamingPrefix() . 'ImageTitle' . $i, $image->GetDescription(), $this->IsValidSubmit());
			$title->SetMaxLength(200);
			$title->AddAttribute('class', 'title');
			$titlelabel = new XhtmlElement('label', 'Caption (up to 15 words)');
			$titlelabel->AddAttribute('for', $title->GetXhtmlId());
			$fields->AddControl($titlelabel);
			$fields->AddControl($title);

			$desc = new TextBox($this->GetNamingPrefix() . 'ImageDesc' . $i, $image->GetLongDescription(), $this->IsValidSubmit());
			$desc->SetMode(TextBoxMode::MultiLine());
			$desclabel = new XhtmlElement('label', 'Longer description (if appropriate)');
			$desclabel->AddAttribute('for', $desc->GetXhtmlId());
			$fields->AddControl($desclabel);
			$fields->AddControl($desc);

			# Add album cover radio button
			$is_cover = new RadioButton($this->GetNamingPrefix() . 'Cover' . $i, $this->GetNamingPrefix() . 'Cover', 'This is the album cover', $image->GetId(), $image->GetId() == $cover_id, $this->IsValidSubmit());
			$fields->AddControl($is_cover);

			$edit->AddControl($fields);
		}

		# If creating a new gallery, next step will be to add photos
		if (!$o_gallery->GetId()) $this->SetButtonText('Continue');
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/words-validator.class.php');
		require_once('http/short-url-validator.class.php');

		$this->a_validators[] = new RequiredFieldValidator('title', 'Please add a title for your album');
        $this->a_validators[] = new LengthValidator('title', 'Please make your album title shorter', 0, 100);
        $this->a_validators[] = new LengthValidator('description', 'Please make your album description shorter', 0, 10000);
		$this->a_validators[] = new ShortUrlValidator($this->GetNamingPrefix() . 'ShortUrl', 'That short URL is already in use', ValidatorMode::SingleField(), $this->GetDataObject());

		$gallery_images = $this->GetDataObject()->Images()->GetItems();
		$total_images = count($gallery_images);
		$images_to_keep = $total_images;
		$cover_id = ($this->GetDataObject()->HasCoverImage()) ? $this->GetDataObject()->GetCoverImage()->GetId() : null;
		$deleting_cover = false;

		for ($i = 1; $i <= $total_images; $i++)
		{
			if ($gallery_images[$i-1]->GetIsForDeletion())
			{
				$images_to_keep--;
				if ($gallery_images[$i-1]->GetItem()->GetId() == $cover_id) $deleting_cover = true;
				continue;
			}

			$this->a_validators[] = new LengthValidator($this->GetNamingPrefix() . 'ImageTitle' . $i, 'Please make your caption for photo ' . $i . ' shorter', 0, 200);
			$this->a_validators[] = new WordsValidator($this->GetNamingPrefix() . 'ImageTitle' . $i, 'Your caption for photo ' . $i . ' should be 15 words or fewer', 0, 15);
			$this->a_validators[] = new LengthValidator($this->GetNamingPrefix() . 'ImageDesc' . $i, 'Please make your description of photo ' . $i . ' shorter', 0, 10000);
		}

		if ($images_to_keep)
		{
			$valid_cover = $deleting_cover ? new RequiredFieldValidator($this->GetNamingPrefix() . 'This_Validator_Will_Fail', 'You\'re removing the cover image &#8211; please select a new cover') : new RequiredFieldValidator($this->GetNamingPrefix() . 'Cover', 'Please select a cover image');
			$valid_cover->SetValidIfNotFound(false);
			$this->a_validators[] = $valid_cover;
		}

		$a_aggregated_validators = $this->season_editor->GetValidators();
		$a_aggregated_validators = array_merge($a_aggregated_validators, $this->teams_editor->GetValidators());
		$this->a_validators = array_merge($this->a_validators, $a_aggregated_validators);
	}
}
?>
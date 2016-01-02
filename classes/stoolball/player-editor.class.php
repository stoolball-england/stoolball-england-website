<?php
require_once('data/data-edit-control.class.php');

/**
 * Edit details of a stoolball player
 *
 */
class PlayerEditor extends DataEditControl
{
	/**
	 * Creates a PlayerEditor
	 * @param SiteSettings $settings
	 * @return void
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->SetDataObjectClass('Player');
		parent::__construct($settings);
	}

	private $merge_requested = false;

	/**
	 * Gets whether the user chose to merge the current player with a duplicate
	 * @return bool
	 */
	public function IsMergeRequested() { return $this->merge_requested; }

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	public function BuildPostedDataObject()
	{
		$player = new Player($this->GetSettings());

		$key = $this->GetNamingPrefix() . 'item';
		if (isset($_POST[$key])) $player->SetId($this->GetNamingPrefix() . $_POST[$key]);

		$key = $this->GetNamingPrefix() . 'Name';
		if (isset($_POST[$key])) $player->SetName($this->GetNamingPrefix() . $_POST[$key]);

		$this->SetDataObject($player);

		$key = $this->GetNamingPrefix() . "MergeOptions";
		if (isset($_POST[$key]) and $_POST[$key] == "1") $this->merge_requested = true;
	}

	public function CreateControls()
	{
		# get player to edit
		$player = $this->GetDataObject();
		if (!$player instanceof Player) $player = new Player($this->GetSettings());

		require_once("xhtml/forms/radio-button.class.php");
		if ($this->GetCurrentPage() == PlayerEditor::MERGE_PLAYER)
		{

			$this->AddControl("<p>There's another player named '" . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "' in this team. What would you like to do?</p>");
			$this->AddControl("<dl class=\"decision\"><dt>");
			$this->AddControl(new RadioButton($this->GetNamingPrefix() . "Merge", $this->GetNamingPrefix() . "MergeOptions", htmlentities("Merge with the other " . $player->GetName(), ENT_QUOTES, "UTF-8", false), 1, false, $this->IsValid()));
			$this->AddControl("</dt><dd>If they're the same person you can merge the records so there's only one " . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . ". Don't choose this for a player who's got married and changed their name though.</dd>");
			$this->AddControl("<dt>");
			$this->AddControl(new RadioButton($this->GetNamingPrefix() . "Rename", $this->GetNamingPrefix() . "MergeOptions", "Choose a new name for this player", 2, false, $this->IsValid()));
			$this->AddControl("</dt><dd>If they're different people you need to pick a different name. For example, if you have two players called 'Jane Smith',
				change one to 'Jane A Smith'.</dd></dl>");
		}


		# Container for form elements, useful for JavaScript to hide them
		$container = new XhtmlElement("div", null, "#playerEditorFields");
		$this->AddControl($container);

		# add name
		$name_box = new TextBox($this->GetNamingPrefix() . 'Name', $player->GetName(), $this->IsValid());
		$name_box->AddAttribute('maxlength', 100);
		$name = new FormPart('Name', $name_box);
		$container->AddControl($name);
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/plain-text-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once 'stoolball/player-name-validator.class.php';

		if ($this->GetCurrentPage() == PlayerEditor::MERGE_PLAYER)
		{
			$radio_required = new RequiredFieldValidator($this->GetNamingPrefix() . "MergeOptions", "Please choose whether to merge the two players");
			$radio_required->SetValidIfNotFound(false);
			$this->AddValidator($radio_required);
		}

		$this->AddValidator(new RequiredFieldValidator($this->GetNamingPrefix() . 'Name', "Please add the player's name"));
		$this->AddValidator(new PlainTextValidator($this->GetNamingPrefix() . 'Name', "Please use only letters, numbers and simple punctuation in the player's name"));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'Name', "Please make the player's name shorter", 0, 100));
		$player_name_validator = new PlayerNameValidator(array($this->GetNamingPrefix() . "Name"), "Sorry, you can't use that name for a player. Please choose another.");
		$player_name_validator->SetSiteSettings($this->GetSettings());
		$this->AddValidator($player_name_validator);
	}

	/**
	 * Show editor for adding or editing a player
	 * @return int
	 */
	const EDIT_PLAYER = 1;

	/**
	 * Show prompt to merge players or go back to edit view
	 * @return int
	 */
	const MERGE_PLAYER = 2;
}
?>
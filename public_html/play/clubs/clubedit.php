<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

if (strpos($_SERVER['REQUEST_URI'], '/school/')===0 and substr($_SERVER['REQUEST_URI'], strlen($_SERVER['REQUEST_URI'])-5) !== "/club") {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/play/schools/edit-school.php');
    exit();
}


require_once('page/stoolball-page.class.php');
require_once('stoolball/clubs/club-manager.class.php');
require_once('stoolball/clubs/club-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	private $o_edit;
	/**
	 * The club to edit
	 *
	 * @var Club
	 */
	private $o_club;
	private $o_club_manager;

	function OnPageInit()
	{
		# new data manager
		$this->o_club_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

		# New edit control
		$this->o_edit = new ClubEditControl($this->GetSettings(), $this->GetCsrfToken());
		$this->RegisterControlForValidation($this->o_edit);

		# run template method
		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->o_club = $this->o_edit->GetDataObject();

		# save data if valid
		if($this->IsValid())
		{
			$i_id = $this->o_club_manager->Save($this->o_club);
			$this->o_club->SetId($i_id);
			$this->Redirect($this->o_club->GetNavigateUrl());
		}
	}

	function OnLoadPageData()
	{
		# get id of club
		$i_id = $this->o_edit->GetDataObjectId();

		# no need to read data if creating a new club
		if ($i_id and !isset($_POST['item']))
		{
			# get club
			$this->o_club_manager->ReadById(array($i_id));
			$this->o_club = $this->o_club_manager->GetFirst();
		}

		# tidy up
		unset($this->o_club_manager);
	}

	function OnPrePageLoad()
	{
		# set page title
		$this->SetPageTitle(is_object($this->o_club) ? $this->o_club->GetName() . ': Edit stoolball club' : 'New stoolball club');
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

		# display the club
		$o_club = (is_object($this->o_club)) ? $this->o_club : new Club($this->GetSettings());
		$this->o_edit->SetDataObject($o_club);
		echo $this->o_edit;

		parent::OnPageLoad();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>
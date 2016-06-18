<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/clubs/club-manager.class.php');
require_once('stoolball/team-manager.class.php');
require_once('stoolball/ground-manager.class.php');
require_once('stoolball/teams/team-edit-control.class.php');

class CurrentPage extends StoolballPage
{
	private $team_manager;
	private $club_manager;
	private $ground_manager;
	/**
	 * The team to edit
	 *
	 * @var Team
	 */
	private $team;
	/**
	 * Editor for the team
	 *
	 * @var TeamEditControl
	 */
	private $edit;

	function OnPageInit()
	{
		# new data managers
		$this->team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $this->team_manager->FilterByTeamType(array());
		$this->club_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());
		$this->ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

		# New edit control
		$this->edit = new TeamEditControl($this->GetSettings());
		$this->RegisterControlForValidation($this->edit);

		parent::OnPageInit();
	}

	function OnPostback()
	{
		# get object
		$this->team = $this->edit->GetDataObject();

        # Check user has permission to edit this team. Authentication for team owners is based on the URI 
        # so make sure we get that from the database, not untrusted user input.
        if ($this->team->GetId() and !AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS)) 
        {
            $this->team_manager->ReadById(array($this->team->GetId()));
            $check_team = $this->team_manager->GetFirst();
            $this->team->SetShortUrl($check_team->GetShortUrl());
        }
        
        if (($this->team->GetId() and !AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $this->team->GetLinkedDataUri()))
             or (!$this->team->GetId() and !AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS)))
        {
            $this->GetAuthenticationManager()->GetPermission();
        } 
        

		# save data if valid
		if($this->IsValid())
		{
			$i_id = $this->team_manager->SaveTeam($this->team);
			$this->team->SetId($i_id);

			$this->Redirect($this->team->GetNavigateUrl());
		}
	}

	function OnLoadPageData()
	{
		# get id of team
		$i_id = $this->team_manager->GetItemId($this->team);

		# no need to read team data if creating a new team
		# unlike some pages though, re-read after a save because not all info is posted back
		if ($i_id)
		{
			# get team
			$this->team_manager->ReadById(array($i_id));
			$this->team = $this->team_manager->GetFirst();
            
            # Check user has permission to edit this team
            if (!$this->team instanceof Team or 
                !AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $this->team->GetLinkedDataUri()))
            {
                $this->GetAuthenticationManager()->GetPermission();
            } 
		}
        else 
        {
            # Check user has permission to create teams
            if (!AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS))
            {
                $this->GetAuthenticationManager()->GetPermission();
            }
        }

		# get all clubs
		$this->club_manager->ReadAll();
		$this->edit->SetClubs($this->club_manager->GetItems());

			# get all grounds
		$this->ground_manager->ReadAll();
		$this->edit->SetGrounds($this->ground_manager->GetItems());

		# tidy up
		unset($this->team_manager);
		unset($this->club_manager);
		unset($this->ground_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle(is_object($this->team) ? $this->team->GetName() . ': Edit stoolball team' : 'New stoolball team');
		$this->SetContentConstraint(StoolballPage::ConstrainText());
        $this->LoadClientScript("/scripts/tiny_mce/jquery.tinymce.js");
        $this->LoadClientScript("/scripts/tinymce.js");
        $this->LoadClientScript("edit-team.js", true);
	}

	function OnPageLoad()
	{
		echo new XhtmlElement('h1', $this->GetPageTitle());

		# display the team
		$team = (is_object($this->team)) ? $this->team : new Team($this->GetSettings());
		$this->edit->SetDataObject($team);
		echo $this->edit;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
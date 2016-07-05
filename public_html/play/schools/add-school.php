<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/schools/add-school-control.class.php');

class CurrentPage extends StoolballPage
{
    /* @var SchoolEditControl */
    private $edit;
    
    /**
     * @var Club 
     */
    private $school;
    
    public function OnPageInit()
    {
        $this->edit = new AddSchoolControl($this->GetSettings());
        $this->RegisterControlForValidation($this->edit);

        parent::OnPageInit();
    }
    
    function OnPostback()
    {
        $this->school = $this->edit->GetDataObject();

        # save data if valid
        if($this->IsValid())
        {
            require_once("stoolball/clubs/club-manager.class.php");
            $club_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());
            $id = $club_manager->Save($this->school);
            $this->school->SetId($id);

            $this->Redirect($this->school->GetNavigateUrl() . "/edit");
        }
    }
    
    public function OnPrePageLoad()
	{
	    $this->SetPageTitle('Add a school');
        $this->SetContentConstraint(StoolballPage::ConstrainText());
        $this->LoadClientScript("/schools.json");
        $this->LoadClientScript("/play/schools/add-school.js");
    }

	public function OnPageLoad()
	{
        ?>
        <h1>Add a school</h1>

		<?php
		echo $this->edit;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/schools/school-edit-control.class.php');
require_once("stoolball/clubs/club-manager.class.php");

class CurrentPage extends StoolballPage
{
    /**
     * @var ClubManager
     */
    private $school_manager;
    
    /* @var SchoolEditControl */
    private $edit;
    
    /**
     * @var Club 
     */
    private $school;
    
    public function OnPageInit()
    {
        $this->edit = new SchoolEditControl($this->GetSettings());
        $this->RegisterControlForValidation($this->edit);
        
        $this->school_manager = new ClubManager($this->GetSettings(), $this->GetDataConnection());

        parent::OnPageInit();
    }
    
    public function OnPostback()
    {
        $this->school = $this->edit->GetDataObject();

        # save data if valid
        if($this->IsValid())
        {
            $this->school_manager->SaveSchool($this->school);
            $this->Redirect($this->school->GetNavigateUrl());
        }
    }
    
    public function OnLoadPageData()
    {
        $id = $this->school_manager->GetItemId();
        $this->school_manager->ReadById(array($id));
        $this->school = $this->school_manager->GetFirst();
        unset($this->school_manager);
    }
    
    
    public function OnPrePageLoad()
	{
	    $this->SetPageTitle('Edit ' . $this->school->GetName());
        $this->SetContentConstraint(StoolballPage::ConstrainText());
    }

	public function OnPageLoad()
	{
        ?>
        <h1><?php echo $this->GetPageTitle() ?></h1>

		<?php
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('About the school' => '');       
		
                
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
            <?php
    		$this->edit->SetDataObject($this->school);
    		echo $this->edit;
            ?>
            </div>
        </div>
        <?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>
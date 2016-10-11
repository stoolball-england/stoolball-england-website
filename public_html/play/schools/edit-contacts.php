<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/schools/edit-school-contacts-control.class.php');
require_once("stoolball/schools/school-manager.class.php");

class CurrentPage extends StoolballPage
{
    /**
     * @var ClubManager
     */
    private $school_manager;
    
    /* @var EditSchoolContactsControl */
    private $edit;
    
    /**
     * @var School 
     */
    private $school;
     
    public function OnPageInit()
    {
        $this->edit = new EditSchoolContactsControl($this->GetSettings());
        $this->RegisterControlForValidation($this->edit);
        
        $this->school_manager = new SchoolManager($this->GetSettings(), $this->GetDataConnection());
    
        parent::OnPageInit();
    }
    
    public function OnPostback()
    {
        $this->school = $this->edit->GetDataObject();

        # save data if valid
        if($this->IsValid())
        {                      
            $this->school_manager->SaveSocialMedia($this->school);
            
            $this->school_manager->ReadById(array($this->school->GetId()));
            $saved_school = $this->school_manager->GetFirst();
            $this->Redirect($saved_school->GetNavigateUrl());
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
	    $this->SetPageTitle('Contact ' . $this->school->GetName());
        $this->SetContentConstraint(StoolballPage::ConstrainText());
    }

	public function OnPageLoad()
	{
        ?>
        <h1><?php echo $this->GetPageTitle() ?></h1>

		<?php
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('About the school' => $this->school->GetEditClubUrl(), 'Contact the school' => '');
		
                
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
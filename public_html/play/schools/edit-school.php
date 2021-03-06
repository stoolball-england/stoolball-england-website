<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/schools/edit-school-control.class.php');
require_once("stoolball/schools/school-manager.class.php");
require_once("stoolball/ground-manager.class.php");

class CurrentPage extends StoolballPage
{
    /**
     * @var ClubManager
     */
    private $school_manager;
    
    /**
     * @var GroundManager
     */
    private $ground_manager;
    
    /* @var SchoolEditControl */
    private $edit;
    
    /**
     * @var School 
     */
    private $school;
    
    
    public function OnSiteInit()
    {
        $this->SetHasGoogleMap(true);
        parent::OnSiteInit();
    }
    
    public function OnPageInit()
    {
        $this->edit = new EditSchoolControl($this->GetSettings(), $this->GetCsrfToken());
        $this->RegisterControlForValidation($this->edit);
        
        $this->school_manager = new SchoolManager($this->GetSettings(), $this->GetDataConnection());
        $this->ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

        parent::OnPageInit();
    }
    
    public function OnPostback()
    {
        $this->school = $this->edit->GetDataObject();

        # save data if valid
        if($this->IsValid())
        {
            require_once('http/short-url-manager.class.php');
            $url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
            $url_manager->EnsureShortUrlAndSave($this->school);
            
            $this->school_manager->SaveSchool($this->school, $this->ground_manager);
            
            $this->Redirect($this->school->GetNavigateUrl());
        }
    }
    
    public function OnLoadPageData()
    {
        $id = $this->edit->GetDataObjectId();
        $this->school_manager->ReadById(array($id));
        $this->school = $this->school_manager->GetFirst();
        unset($this->school_manager);
        
        $this->ground_manager->ReadGroundForSchool($this->school);
        $ground = $this->ground_manager->GetFirst();
        if ($ground instanceof Ground) {
            $this->school->Ground()->SetId($ground->GetId());
            $this->school->Ground()->SetAddress($ground->GetAddress());
        }
    }
    
    
    public function OnPrePageLoad()
	{
	    $this->SetPageTitle('Edit ' . $this->school->GetName());
        $this->SetContentConstraint(StoolballPage::ConstrainText());
        $this->LoadClientScript('/scripts/maps-3.js');
        $this->LoadClientScript("/play/schools/edit-school.js");
    }

	public function OnPageLoad()
	{
        ?>
        <h1><?php echo $this->GetPageTitle() ?></h1>

		<?php
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('About the school' => '', 'Contact the school' => $this->school->EditContactsUrl());       
		
                
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
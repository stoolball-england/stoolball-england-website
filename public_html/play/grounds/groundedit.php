<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('stoolball/ground-manager.class.php');
require_once ('stoolball/ground-edit-control.class.php');

class CurrentPage extends StoolballPage
{
    private $ground_manager;
    /**
     * The ground to edit
     *
     * @var Ground
     */
    private $ground;
    private $editor;

    public function OnSiteInit()
    {
        $this->SetHasGoogleMap(true);
        parent::OnSiteInit();
    }

    function OnPageInit()
    {
        # new data manager
        $this->ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

        # New edit control
        $this->editor = new GroundEditControl($this->GetSettings());
        $this->RegisterControlForValidation($this->editor);

        parent::OnPageInit();
    }

    function OnPostback()
    {
        # get object
        $this->ground = $this->editor->GetDataObject();

        # save data if valid
        if ($this->IsValid())
        {
            $i_id = $this->ground_manager->SaveGround($this->ground);
            $this->ground->SetId($i_id);

            $this->Redirect($this->ground->GetNavigateUrl());
        }
    }

    function OnLoadPageData()
    {
        # get id of ground
        $i_id = $this->ground_manager->GetItemId($this->ground);

        # no need to read ground data if creating a new ground
        if ($i_id and !$this->IsPostback())
        {
            # get ground info
            $this->ground_manager->ReadById(array($i_id));
            $this->ground = $this->ground_manager->GetFirst();
        }

        # tidy up
        unset($this->ground_manager);
    }

    function OnPrePageLoad()
    {
        # set page title
        $this->SetPageTitle(is_object($this->ground) ? $this->ground->GetNameAndTown() . ': Edit stoolball ground' : 'New stoolball ground');
        $this->LoadClientScript('/scripts/maps-3.js');
        $this->LoadClientScript('/play/grounds/groundedit.js');
    }

    function OnPageLoad()
    {
        echo new XhtmlElement('h1', $this->GetPageTitle());

        # display the ground
        $ground = (is_object($this->ground)) ? $this->ground : new Ground($this->GetSettings());
        $this->editor->SetDataObject($ground);
        echo $this->editor;

        parent::OnPageLoad();
    }

}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_GROUNDS, false);
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('stoolball/competition-manager.class.php');
require_once ('stoolball/competition-edit-control.class.php');
require_once ('stoolball/season-manager.class.php');

class CurrentPage extends StoolballPage
{
    var $o_comp_manager;
    var $o_season_manager;
    /**
     * The competition to edit
     *
     * @var Competition
     */
    private $o_competition;
    private $o_edit;

    function OnPageInit()
    {
        # new data managers
        $this->o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        $this->o_season_manager = new SeasonManager($this->GetSettings(), $this->GetDataConnection());

        # data edit control
        $this->o_edit = new CompetitionEditControl($this->GetSettings(), $this->GetCategories());
        $this->RegisterControlForValidation($this->o_edit);

        parent::OnPageInit();
    }

    function OnPostback()
    {
        $this->o_competition = $this->o_edit->GetDataObject();

        # save data if valid
        if ($this->IsValid())
        {
            $i_id = $this->o_comp_manager->SaveCompetition($this->o_competition);
            $this->o_competition->SetId($i_id);

            $this->Redirect($this->o_competition->GetNavigateUrl());
        }
    }

    function OnLoadPageData()
    {
        /* @var $o_competition Competition */
        /* @var $o_season_manager SeasonManager */

        # get id of competition
        $i_id = $this->o_comp_manager->GetItemId($this->o_competition);

        # no need to read competition data if creating a new competition
        # unlike some pages though, re-read after a save because not all info is
        # posted back
        if ($i_id)
        {
            # get competition info
            $this->o_comp_manager->ReadById(array($i_id));
            $this->o_competition = $this->o_comp_manager->GetFirst();

            # Get all seasons
            $a_comp_ids = array($i_id);
            $this->o_season_manager->ReadByCompetitionId($a_comp_ids);
            $this->o_competition->SetSeasons($this->o_season_manager->GetItems());
        }

        # tidy up
        unset($this->o_comp_manager);
        unset($this->o_season_manager);
    }

    function OnPrePageLoad()
    {
        /* @var $o_competition Competition */
        /* @var $o_season Season */

        $this->SetPageTitle(is_object($this->o_competition) ? $this->o_competition->GetName() . ': Edit stoolball competition' : 'New stoolball competition');
    }

    function OnPageLoad()
    {
        echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

        # display the competition
        $o_competition = (is_object($this->o_competition)) ? $this->o_competition : new Competition($this->GetSettings());
        $this->o_edit->SetDataObject($o_competition);
        echo $this->o_edit;

        parent::OnPageLoad();
    }

}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_COMPETITIONS, false);
?>
<?php
if (substr(strtolower(trim($_SERVER['REQUEST_URI'], '/')), 0, 25) == "competitions/surreyladies")
{
    # Pass request to WordPress
    $_SERVER['REQUEST_URI'] = "/category/surreyladies/";
    require('../../index.php');
    exit();
}

if (substr(strtolower(trim($_SERVER['REQUEST_URI'], '/')), 0, 17) == "competitions/scsa")
{
    # Pass request to WordPress
    $_SERVER['REQUEST_URI'] = "/play/sussex-county-stoolball-association/";
    require('../../index.php');
    exit();
}


ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/competition-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $competitions;
    private $content;

	function OnLoadPageData()
	{
		# new data manager
		$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());

		# get competitions
		$o_comp_manager->SetExcludeInactive(!AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_COMPETITIONS));
		$o_comp_manager->ReadCompetitionsInCategories();
		$this->competitions = $o_comp_manager->GetItems();

		# tidy up
		unset($o_comp_manager);
	}

	function OnPrePageLoad()
	{
        $this->content = new XhtmlElement("div");
        $category = $this->DisplayCompetitions($this->content);

        if (!$category)
        {
            $category = "Stoolball ";
        } 
        
		$this->SetPageTitle("$category leagues and competitions");
        $this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

	function OnPageLoad()
	{
  		echo new XhtmlElement('h1', htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false));
        echo new XhtmlElement('div', $this->content, "play");

        $this->ShowSocialAccounts();
			
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_COMPETITIONS))
		{
	        $this->AddSeparator();
			        
	        require_once('stoolball/user-edit-panel.class.php');
			$panel = new UserEditPanel($this->GetSettings(), '');
			$panel->AddLink('add a competition', '/play/competitions/competitionedit.php');
			echo $panel;
		} 
	}
    
    private function DisplayCompetitions(XhtmlElement $container)
    {
        /* @var $o_competition Competition */
        /* @var $o_season Season */

        $b_list_open = false;
        $i_current_category = null;
        $category_limit = isset($_GET["category"]) ? $_GET["category"] : null;
        $male_image = '<img src="/images/play/catcher.jpg" alt="" width="280" height="170" />';
        $female_image = '<img src="/images/play/winterfold.jpg" alt="" width="238" height="195" />';
        $category = "";

        if (!$category_limit)
        {
            $container->AddControl($male_image . '<nav><ul class="nav">');
            $b_list_open = true;
        }

        foreach ($this->competitions as $o_competition)
        {
            $comp_name = $o_competition->GetNameAndType();

            if ($o_competition->GetCategory() != null)
            {
                if ($o_competition->GetCategory()->GetId() != $i_current_category)
                {
                    if (!$category_limit)
                    {
                        $container->AddControl('<li><a href="/competitions/' . htmlentities($o_competition->GetCategory()->GetUrl(), ENT_QUOTES, "UTF-8", false) . '">' . 
                                                htmlentities($o_competition->GetCategory()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a></li>');
                    }
                    else
                    {
                        if ($b_list_open)
                        {
                            $container->AddControl('</ul></nav>');
                            $b_list_open = false;
                        }

                        if ($o_competition->GetCategory()->GetUrl() == $category_limit)
                        {
                            $category = $o_competition->GetCategory()->GetName();
                            if (strpos(strtolower($category),"mixed") !== false)
                            { 
                                $container->AddControl($male_image);
                            }
                            else 
                            {
                                $container->AddControl($female_image);
                            }
                        }
                    }
                    $i_current_category = $o_competition->GetCategory()->GetId();
                }
                $comp_name = trim(str_replace($o_competition->GetCategory()->GetName(), '', $comp_name));

                # If not initial view, link to the competition
                if ($o_competition->GetCategory()->GetUrl() == $category_limit)
                {
                    if (!$b_list_open)
                    {
                        $container->AddControl('<nav><ul class="nav">');
                        $b_list_open = true;
                    }
    
                    $o_link = new XhtmlElement('a', htmlentities($comp_name, ENT_QUOTES, "UTF-8", false));
                    $o_link->AddAttribute('href', $o_competition->GetLatestSeason()->GetNavigateUrl());
                    $o_li = new XhtmlElement('li');
                    $o_li->AddControl($o_link);
                    if (!$o_competition->GetIsActive())
                    {
                        $o_li->AddControl(' (not played any more)');
                    }
    
                    $container->AddControl($o_li);
                }
            }
        }
        
        if ($b_list_open)
        {
            $container->AddControl('</ul></nav>');
        }
        
        return $category;
    }
    
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
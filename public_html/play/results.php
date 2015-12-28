<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('data/data-edit-repeater.class.php');
require_once('stoolball/match-result-edit-control.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends StoolballPage
{
	private $i_team_id;
	private $i_season_id;
    private $tournament_id;
	/**
	 * Season, if results for a season are being edited
	 *
	 * @var Season
	 */
	private $season;
	/**
	 * Team, if results for a team are being edited
	 *
	 * @var Team
	 */
	private $team;
    
    /**
     * Tournament, if results for a tournament are being edited
     * @var Match
     */
    private $tournament;
	private $a_matches;
	/**
	 * Repeater control to edit a series of match results
	 *
	 * @var DataEditRepeater
	 */
	private $repeater;
	private $b_saved = false;

	function OnSiteInit()
	{
		parent::OnSiteInit();

		# check parameters
		if (isset($_GET['team']) and is_numeric($_GET['team'])) $this->i_team_id = (int)$_GET['team'];
		else if (isset($_POST['team']) and is_numeric($_POST['team'])) $this->i_team_id = (int)$_POST['team'];
		else if (isset($_GET['season']) and is_numeric($_GET['season'])) $this->i_season_id = (int)$_GET['season'];
		else if (isset($_POST['season']) and is_numeric($_POST['season'])) $this->i_season_id = (int)$_POST['season'];
        else if (isset($_GET['tournament']) and is_numeric($_GET['tournament'])) $this->tournament_id = (int)$_GET['tournament'];
        else if (isset($_POST['tournament']) and is_numeric($_POST['tournament'])) $this->tournament_id = (int)$_POST['tournament'];
		else $this->Redirect();
	}

	function OnLoadPageData()
	{
		/* @var $o_match Match */
		/* @var $o_team Team */

		# new data manager
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# create repeater control, and save any posted data
		$this->repeater = new DataEditRepeater($this, 'CreateEditControl');
		$this->repeater->SetCssClass('matchResults');
		$this->repeater->SetPersistedParameters(array('team', 'season',"tournament"));
		$this->repeater->SetButtonText('Save all results');
		$this->repeater->SetShowButtonsAtTop(true);

		if ($this->IsPostback() and !$this->IsRefresh() and $this->IsValid())
		{
			require_once('forums/topic-manager.class.php');
			require_once('forums/review-item.class.php');
			require_once('forums/subscription-manager.class.php');
			$topic_manager = new TopicManager($this->GetSettings(), $this->GetDataConnection());
			$comments_category = $this->GetSettings()->GetCommentsCategory($this->GetCategories(), ContentType::STOOLBALL_MATCH);

		    require_once ("search/lucene-search.class.php");
            $search = new LuceneSearch();
        
            foreach ($this->repeater->GetDataObjects() as $o_current_match)
			{
				/* @var $o_current_match Match */
				$match_manager->SaveResult($o_current_match);
				$match_manager->NotifyMatchModerator($o_current_match->GetId());

				if (trim($o_current_match->GetNewComment()))
				{
					$item_to_comment_on = new ReviewItem($this->GetSettings());
					$item_to_comment_on->SetType(ContentType::STOOLBALL_MATCH);
					$item_to_comment_on->SetId($o_current_match->GetId());
					$topic = $topic_manager->SaveComment($item_to_comment_on, $comments_category, $o_current_match->GetTitle(), $o_current_match->GetNewComment(), null);

					# send subscription emails - new object each time to reset list of who's already recieved an email
					$subs_manager = new SubscriptionManager($this->GetSettings(), $this->GetDataConnection());
					$subs_manager->SetTopic($topic);
					$subs_manager->SendCommentsSubscriptions($item_to_comment_on);
					unset($subs_manager);
				}
            }

		    $search->CommitChanges();
        
        	$this->b_saved = true;
		}

		# get matches
		if (!is_null($this->i_team_id))
		{
			$a_season_times = Season::SeasonDates();
            $match_manager->FilterByTeam(array($this->i_team_id));
            $match_manager->FilterByDateStart($a_season_times[0]);
            $match_manager->FilterByDateEnd($a_season_times[1]);
			$match_manager->ReadMatchSummaries();
        }
		else if (!is_null($this->i_season_id))
		{
			$match_manager->ReadBySeasonId(array($this->i_season_id), true);
		}
        else if (!is_null($this->tournament_id)) 
        {
            $match_manager->FilterByTournament($this->tournament_id);
            $this->a_matches = $match_manager->ReadMatchSummaries();
        }
        $this->a_matches = $match_manager->GetItems();
        		
		# Make sure we have some matches
		if (count($this->a_matches))
		{
			# If it's matches for a team, get the team
			if (!is_null($this->i_team_id))
			{
				# Should only need to look at the first match, because the team must be a
				# part of that match in order for the match to be in this list
				$o_match = &$this->a_matches[0];

				$o_team = $o_match->GetHomeTeam();
				if ($o_team instanceof Team and $o_team->GetId() == $this->i_team_id)
				{
					$this->team = $o_team;
				}
				else
				{
					foreach ($o_match->GetAwayTeams() as $o_team)
					{
						if ($o_team->GetId() == $this->i_team_id)
						{
							$this->team = $o_team;
							break;
						}
					}
				}
				if (!is_object($this->team)) $this->Redirect();

				# Now that we have short URL data, if data has just been saved for team, redirect back to team
				if ($this->b_saved) $this->Redirect($this->team->GetNavigateUrl());
			}
			else if (!is_null($this->i_season_id))
			{
				# get details of the season
				require_once('stoolball/competition-manager.class.php');
				$o_comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
				$o_comp_manager->ReadById(null, array($this->i_season_id));
				$o_competition = $o_comp_manager->GetFirst();
				unset($o_comp_manager);
				if ($o_competition instanceof Competition)
				{
					$this->season = $o_competition->GetWorkingSeason();
				}
				if (!$this->season instanceof Season) $this->Redirect();

				# Now that we have short URL data, if data has just been saved for season, redirect back to season
				if ($this->b_saved) $this->Redirect($this->season->GetNavigateUrl());
			}
            else if (!is_null($this->tournament_id)) 
            {
                $match_manager->ReadByMatchId(array($this->tournament_id));
                $this->tournament = $match_manager->GetFirst();
                if (!is_null($this->tournament))
                {
                    # If the tournament has just been saved, now we have its URL so redirect to the thanks page
                    if ($this->b_saved) $this->Redirect($this->tournament->GetNavigateUrl());
                }                
            }
		}
		else
		{
			$this->Redirect();
		}

        unset($match_manager);
	}

	function OnPrePageLoad()
	{
		if (!is_null($this->team))
		{
			$this->SetPageTitle('Match results for ' . $this->team->GetName());
		}
		else if (!is_null($this->season))
		{
			$this->SetPageTitle('Match results for ' . $this->season->GetCompetitionName());
		}
        else if (!is_null($this->tournament)) 
        {
             $this->SetPageTitle('Match results for ' . $this->tournament->GetTitle());
        }

		$this->LoadClientScript('/scripts/match-results-3.js');
	}

	function OnPageLoad()
	{
		echo '<h1>' . htmlentities($this->GetPageTitle(), ENT_QUOTES, "UTF-8", false) . '</h1>';

        /* Create instruction panel */
		$o_panel_inner2 = new XhtmlElement('div');
		$o_panel_inner1 = new XhtmlElement('div', $o_panel_inner2);
		$o_panel = new XhtmlElement('div', $o_panel_inner1);
		$o_panel->SetCssClass('panel instructionPanel');

		$o_title_inner3 = new XhtmlElement('span', 'Add your results quickly:');
		$o_title_inner2 = new XhtmlElement('span', $o_title_inner3);
		$o_title_inner1 = new XhtmlElement('span', $o_title_inner2);
		$o_title = new XhtmlElement('h2', $o_title_inner1, "large");
		$o_panel_inner2->AddControl($o_title);

		$o_tab_tip = new XhtmlElement('ul');
		$o_tab_tip->AddControl(new XhtmlElement('li', 'Use the <span class="tab">tab</span> key on your keyboard to move through the form', "large"));
		$o_tab_tip->AddControl(new XhtmlElement('li', 'Type the first letter or number to select from a dropdown list', "large"));
		$o_tab_tip->AddControl(new XhtmlElement('li', 'Don\'t worry if you don\'t know &#8211; fill in what you can and leave the rest blank.'));
		$o_panel_inner2->AddControl($o_tab_tip);
		echo $o_panel;
		
		# display the matches to edit, with some filtering and sorting for teams and seasons
		if (is_null($this->tournament_id))
        { 
    		$i_user_now =  gmdate('U');
    		$i_tonight = gmmktime(11, 59, 59, gmdate('m', $i_user_now), gmdate('d', $i_user_now), gmdate('Y', $i_user_now));
    		$i_week_ago = $i_user_now - (60*60*24*7);
    		$i_matches = count($this->a_matches);
    		$a_this_week = array();
    		for ($i = 0; $i < $i_matches; $i++)
    		{
    			/* @var $o_match Match */
    			$o_match = $this->a_matches[$i];
    
    			# If it's a practice, there's no result so don't show it
    			# If it's a tournament, we're not handling those yet so don't show
    			if ($o_match->GetMatchType() == MatchType::PRACTICE or $o_match->GetMatchType() == MatchType::TOURNAMENT)
    			{
    				unset($this->a_matches[$i]);
    				continue;
    			}
    
    			# If it's a tournament match, only show if it's being specifically edited, not as part of season
    			if ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH and $o_match->GetId() != $this->i_match_id)
    			{
    				unset($this->a_matches[$i]);
    				continue;
    			}
    
    			# If match was in the last week, promote it to top of list
    			if ($o_match->GetStartTime() >= $i_week_ago and $o_match->GetStartTime() <= $i_tonight)
    			{
    				unset($this->a_matches[$i]);
    				$a_this_week[] = $o_match;
    			}
    		}
    		foreach ($a_this_week as $o_match)
            {
                $this->repeater->AddDataObject($o_match, 1);
            }
        }
		foreach ($this->a_matches as $o_match)
        {
            $this->repeater->AddDataObject($o_match, 2);
        }
		if (isset($a_this_week) and count($a_this_week) and count($this->a_matches))
		{
			$this->repeater->SetSectionHeading(1, 'This week\'s matches');
			$this->repeater->SetSectionHeading(2, 'Other matches');
		}
		else
		{
			$this->repeater->SetShowSections(false);
		}

		echo $this->repeater;
    }

	/**
	 * Factory method to return a DataEditControl for the DataEditRepeater
	 *
	 * @return DataEditControl
	 */
	public function CreateEditControl()
	{
		$o_edit_control = new MatchResultEditControl($this->GetSettings());
		$o_edit_control->SetCssClass('panel');
		$this->RegisterControlForValidation($o_edit_control);
		return $o_edit_control;
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>
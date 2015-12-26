<?php
require_once('stoolball/user-edit-panel.class.php');

/**
 * Edit panel with options relevant to a specific team
 *
 */
class TeamEditPanel extends UserEditPanel
{
	/**
	 * Creates a TeamEditPanel
	 *
	 * @param SiteSettings $settings
	 * @param Team $team
	 * @param Season[] $seasons
	 * @param Match[] $matches
	 */
	public function __construct(SiteSettings $settings, Team $team, $seasons, $matches)
	{
		parent::__construct($settings, " this team");

        $is_one_time_team = ($team->GetTeamType() == Team::ONCE);
        if (!$is_one_time_team)
        { 
		  $this->AddLink('tell us about your team', $settings->GetUrl('TeamAdd'));
        }
        
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS, $team->GetLinkedDataUri()))
		{
			$this->AddLink('edit this team', $team->GetEditTeamUrl());
		}

        if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_TEAMS))
        {
            $this->AddLink('delete this team', $team->GetDeleteTeamUrl());
        }

		if (!$is_one_time_team)
        {
            $b_in_league = false;
    		$b_in_cup = false;
    		if (is_array($seasons))
    		{
        		foreach ($seasons as $season)
        		{
        			/* @var $season Season */
        			if (!$b_in_cup and $season->MatchTypes()->Contains(MatchType::CUP)) $b_in_cup = true;
        			if (!$b_in_league and $season->MatchTypes()->Contains(MatchType::LEAGUE)) $b_in_league = true;
        			if ($b_in_cup and $b_in_league) break;
        		}
    		}
    
    		$this->AddLink('add practice', $team->GetAddMatchNavigateUrl(MatchType::PRACTICE));
    		$this->AddLink('add friendly match', $team->GetAddMatchNavigateUrl(MatchType::FRIENDLY));
    		$this->AddLink('add tournament', $team->GetAddMatchNavigateUrl(MatchType::TOURNAMENT));
    		if ($b_in_league) $this->AddLink('add league match', $team->GetAddMatchNavigateUrl(MatchType::LEAGUE));
    		if ($b_in_cup) $this->AddLink('add cup match', $team->GetAddMatchNavigateUrl(MatchType::CUP));
    
    		if (is_array($matches) and count($matches))
    		{
    			# Make sure there's at least one match which is not a tournament or a practice
    			foreach ($matches as $o_match)
    			{
    				/* @var $o_match Match */
    				if ($o_match->GetMatchType() == MatchType::PRACTICE or $o_match->GetMatchType() == MatchType::TOURNAMENT or $o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
    				{
    					continue;
    				}
    				else
    				{
    					$this->AddLink('update results', $team->GetResultsNavigateUrl());
    					break;
    				}
    			}
    			$this->AddLink('add matches to your calendar', $team->GetCalendarNavigateUrl());
    		}
		}
	}
}
?>
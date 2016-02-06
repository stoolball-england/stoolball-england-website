<?php
namespace Stoolball\Statistics;

require_once("xhtml/html.class.php");
require_once("xhtml/placeholder.class.php");

/**
 * Displays filtered summary statistics about the matches a player has played
 */
class PlayerSummaryControl extends \Placeholder {
        
	/**
     * @var \Player 
     */
    private $player;
    private $filter_description;
    private $filter_matched_nothing;
    private $filter_querystring;
    
	public function __construct(\Player $player, $filter_description, $filter_matched_nothing, $filter_querystring) {
	   $this->player = $player;	
       $this->filter_description = $filter_description;
       $this->filter_matched_nothing = $filter_matched_nothing;
       $this->filter_querystring = $filter_querystring;
	}
    
    public function OnPreRender() {
            
        $by = ($this->player->GetPlayerRole() == \Player::PLAYER) ? ", " : " conceded by ";
        $this->AddControl('<h1><span property="schema:name">' . \Html::Encode($this->player->GetName()) . '</span>' . \Html::Encode($by . ' ' . $this->player->Team()->GetName() . $this->filter_description) . "</h1>");

        # When has this player played?
        $match_or_matches = ($this->player->GetTotalMatches() == 1) ? " match" : " matches";

        $years = $this->player->GetPlayingYears();

        $filtered = "";
        if ($this->filter_description)
        {
            # If first played date is missing, the player came from PlayerManager because there were no statistics found
            if (!$this->filter_matched_nothing)
            {
                $filtered = " matching this filter";
            }
            else
            {
                $filtered = ", but none match this filter";
                $years = '';
            }
        }

        $team_name = '<span rel="schema:memberOf"><span about="' . \Html::Encode($this->player->Team()->GetLinkedDataUri()) . '" typeof="schema:SportsTeam"><a property="schema:name" rel="schema:url" href="' . \Html::Encode($this->player->Team()->GetNavigateUrl()) . "\">" . \Html::Encode($this->player->Team()->GetName()) . "</a></span></span>";

        if ($this->player->GetPlayerRole() == \Player::PLAYER)
        {
            if ($this->player->GetTotalMatches() == 0) 
            {
                $played_total = " hasn't played any " . $match_or_matches;
            }
            else 
            {
                $played_total = ' played <a href="/play/statistics/player-performances' . $this->filter_querystring . '">' . $this->player->GetTotalMatches() . $match_or_matches . '</a>';
            }
            $this->AddControl("<p>" . \Html::Encode($this->player->GetName()) . $played_total . " for ${team_name}${filtered}${years}.</p>");

            # Player of match
            if ($this->player->GetTotalPlayerOfTheMatchNominations() > 0)
            {
                $match_or_matches = ($this->player->GetTotalPlayerOfTheMatchNominations() == 1) ? " match." : " matches.";
                $this->AddControl('<p>Nominated <a href="/play/statistics/player-of-match' . $this->filter_querystring . '">player of the match</a> in ' . $this->player->GetTotalPlayerOfTheMatchNominations() . $match_or_matches . "</p>");
            }
        }
        else
        {
            $this->AddControl("<p>$team_name recorded " . \Html::Encode($this->player->TotalRuns() . " " . strtolower($this->player->GetName()) . " in " . $this->player->GetTotalMatches() . $match_or_matches . "${filtered}${years}.") . "</p>");
        }
    }
}

?>
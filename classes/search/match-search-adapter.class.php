<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a match into an entry for the search index
 */
class MatchSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    private $match;
    
    public function __construct(Match $match) {
        
        $this->match = $match;
        
        $this->searchable = new SearchItem("match", "match" . $match->GetId(), $match->GetNavigateUrl());
        $this->searchable->Title($match->GetTitle() . ", " . $match->GetStartTimeFormatted(true, false));
        $this->searchable->Description($this->GetSearchDescription());
        $this->searchable->FullText($match->GetNotes());
        $this->searchable->RelatedLinksHtml('<ul>' .
                                            '<li><a href="' . $match->GetCalendarNavigateUrl() . '">Add to calendar</a></li>' .
                                            '</ul>');
   }
    
    /**
     * Gets text to use as the description of this match in search results
     */
    public function GetSearchDescription() 
    {
        $description = "";

        # Display match type/season/tournament
        if ($this->match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
        {
            $o_tourney = $this->match->GetTournament();
            if (is_object($o_tourney))
            {
                # Check for 'the' to get the grammar right
                $s_title = strtolower($o_tourney->GetTitle());
                $the = (strlen($s_title) >= 4 and substr($s_title, 0, 4) == 'the ') ? "" : "the ";
                $description = "Match in $the" . $o_tourney->GetTitle();
                if (is_object($this->match->GetGround())) $description .= " at " . $this->match->GetGround()->GetNameAndTown();
                $description .= ".";
            }
        }
        else
        {
            $description = ucfirst(MatchType::Text($this->match->GetMatchType()));
            if (is_object($this->match->GetGround())) $description .= " at " . $this->match->GetGround()->GetNameAndTown();
            
            if ($this->match->GetMatchType() != MatchType::TOURNAMENT)
            {
                $season_list = "";
    
                if ($this->match->Seasons()->GetCount() == 1)
                {
                    $season = $this->match->Seasons()->GetFirst();
                    $b_the = !(stristr($season->GetCompetition()->GetName(), 'the ') === 0);
                    $description .= ' in ' . ($b_the ? 'the ' : '') . $season->GetCompetition()->GetName();
                }
                elseif ($this->match->Seasons()->GetCount() > 1)
                {
                    $description .= ' in ';
    
                    $seasons = $this->match->Seasons()->GetItems();
                    $total_seasons = count($seasons);
                    for ($i = 0; $i < $total_seasons; $i++)
                    {
                        $season = $seasons[$i];
                        /* @var $season Season */
                        $description .= $season->GetCompetition()->GetName();
                        if ($i < $total_seasons-2)
                        {
                            $description .= ', ';
                        }
                        else if ($i < $total_seasons-1)
                        {
                            $description .= ' and ';
                        }
                    } 
                }

                $description .= $season_list;

            }
            
            $description .= '.';
        }

        return $description;
    }

    /**
     * Gets a searchable item representing the match in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}

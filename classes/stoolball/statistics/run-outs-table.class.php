<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of run-outs
 * @author Rick Mason
 *
 */
class RunOutsTable extends XhtmlTable
{
    private $performance_data;
    private $first_result;

    public function __construct(array $performance_data, $first_result = 1)
    {
        $this->performance_data = $performance_data;
        $this->first_result = (int)$first_result;

        parent::XhtmlTable();

        if (count($performance_data) > 0) 
        {
            $performance = $performance_data[0];
    
            # Create the table header
            $this->SetCaption("Run-outs, most recent first");
            $this->SetCssClass('player-performances');
            $fields = array();
            
            $player = new XhtmlCell(true, "Player");
            $player->SetCssClass("player");
            $fields[] = $player;
            
            $match = new XhtmlCell(true, "Match");
            $fields[] = $match;
            
            $when = new XhtmlCell(true, "When");
            $fields[] = $when;

            $runs = new XhtmlCell(true, "Runs");
            $runs->SetCssClass("numeric");
            $fields[] = $runs;
        
            $header = new XhtmlRow($fields);
            $header->SetIsHeader(true);
            $this->AddRow($header);
        }
    }

    public function OnPreRender()
    {
        foreach ($this->performance_data as $performance)
        {           
            $fields = array();

            $player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a>");
            $player->SetCssClass("player");
            $player->AddAttribute("typeof", "schema:Person");
            $player->AddAttribute("about", "https://www.stoolball.org.uk/id/player" . $performance["player_url"]);
            $fields[] = $player;
            
            $match = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["match_url"] . '">' . $performance["match_title"] . "</a>");
            $match->AddAttribute("typeof", "schema:SportsEvent");
            $match->AddAttribute("about", "https://www.stoolball.org.uk/id/match" . $performance["match_url"]);
            $match->SetCssClass("match");
            $fields[] = $match;
            
            $date = new XhtmlCell(false, Date::BritishDate($performance["match_time"], false, true, true));
            $date->SetCssClass("date");
            $fields[] = $date;

            $runs = new XhtmlCell(false, $performance["runs_scored"]);
            $runs->SetCssClass("numeric");
            $runs->AddCssClass("out");
            $fields[] = $runs;

            $row = new XhtmlRow($fields);
            $this->AddRow($row);
        }

        parent::OnPreRender();
    }
}
?>
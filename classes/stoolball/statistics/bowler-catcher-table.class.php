<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of bowlers and catchers that have taken wickets together
 * @author Rick
 *
 */
class BowlerCatcherTable extends XhtmlTable
{
    private $performance_data;
    private $first_result;

    public function __construct(array $performance_data, $first_result = 1)
    {
        $this->performance_data = $performance_data;
        $this->first_result = (int)$first_result;

        parent::__construct();
        $this->SetCssClass("bowling");

        if (count($performance_data) > 0) 
        {
            $performance = $performance_data[0];
    
            # Create the table header
            $this->SetCaption("Most wickets by a bowling and catching combination");
            $fields = array();
            
            $position = new XhtmlCell(true, "#");
            $position->SetCssClass("position");
            $fields[] = $position;

            $bowler = new XhtmlCell(true, "Bowler");
            $bowler->SetCssClass("player");
            $fields[] = $bowler;
            
            $catcher = new XhtmlCell(true, "Catcher");
            $catcher->SetCssClass("player");
            $fields[] = $catcher;

            $team = new XhtmlCell(true, "Team");
            $fields[] = $team;

            $matches = new XhtmlCell(true, 'Matches <span class="qualifier">(where wickets were taken)</span>');
            $matches->SetCssClass("numeric");
            $fields[] = $matches;

            $wickets = new XhtmlCell(true, "Wickets");
            $wickets->SetCssClass("numeric");
            $fields[] = $wickets;
            
            $header = new XhtmlRow($fields);
            $header->SetIsHeader(true);
            $this->AddRow($header);
        }
    }

    public function OnPreRender()
    {
        $i = $this->first_result;
        $last_value = "";
        foreach ($this->performance_data as $performance)
        {
            $current_value = $performance["wickets"];
            
            if ($current_value != $last_value)
            {
                $pos = $i;
                $last_value = $current_value;
            }
            else
            {
                $pos = "=";
            }
            $i++;

            $fields = array();

            $position = new XhtmlCell(false, $pos);
            $position->SetCssClass("position");
            $fields[] = $position;        

            $bowler = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["bowler_url"] . '">' . $performance["bowler_name"] . "</a>");
            $bowler->SetCssClass("player");
            $bowler->AddAttribute("typeof", "schema:Person");
            $bowler->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["bowler_url"]);
            $fields[] = $bowler;
            
            $catcher = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["catcher_url"] . '">' . $performance["catcher_name"] . "</a>");
            $catcher->SetCssClass("player");
            $catcher->AddAttribute("typeof", "schema:Person");
            $catcher->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["catcher_url"]);
            $fields[] = $catcher;
                        
            $team = new XhtmlCell(false, $performance["team_name"]);
            $team->SetCssClass("team");
            $fields[] = $team;
                        
            $matches = new XhtmlCell(false, $performance["total_matches"]);
            $matches->SetCssClass("numeric");
            $fields[] = $matches;

            $wickets = new XhtmlCell(false, $performance["wickets"]);
            $wickets->SetCssClass("numeric");
            $fields[] = $wickets;

            $row = new XhtmlRow($fields);
            $this->AddRow($row);
        }

        parent::OnPreRender();
    }
}
?>
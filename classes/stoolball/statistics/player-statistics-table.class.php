<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of statistical data for players
 * @author Rick
 *
 */
class PlayerStatisticsTable extends XhtmlTable
{
	private $data;
	private $display_team;
	private $first_result;

	/**
	 * Create a PlayerStatisticsTable
	 * @param $caption
	 * @param $column_heading
	 * @param $data mixed[]
	 * @param $display_team bool
	 * @param int $first_result
	 * @return void
	 */
	public function __construct($caption, $column_heading, $data, $display_team=true, $first_result = 1)
	{
		$this->data = $data;
		$this->display_team = (bool)$display_team;
		$this->first_result = (int)$first_result;

		parent::XhtmlTable();

        if (count($data) > 0) 
        {
            $performance = $data[0];

    		# Create the table header
    		$this->SetCaption($caption);
    		$this->SetCssClass('statsOverall');

            $columns = array();
    		$position = new XhtmlCell(true, "#");
    		$position->SetCssClass("position");
            $columns[] = $position;
            
    		$player = new XhtmlCell(true, "Player");
    		$player->SetCssClass("player");
            $columns[] = $player;
            
    		if ($display_team)
    		{
    			$team = new XhtmlCell(true, "Team");
    			$team->SetCssClass("team");
                $columns[] = $team;
    		}
            
            if (array_key_exists("total_matches", $performance)) {
                $matches = new XhtmlCell(true, "Matches");
                $matches->SetCssClass("numeric");
                $columns[] = $matches;
            }

    		$aggregate = new XhtmlCell(true, $column_heading);
    		$aggregate->SetCssClass("numeric");
            $columns[] = $aggregate;

			$header = new XhtmlRow($columns);
    		$header->SetIsHeader(true);
    		$this->AddRow($header);
        }
	}

	public function OnPreRender()
	{
		$i = $this->first_result;
		$last_value = "";
        if (is_array($this->data)) 
        {
    		foreach ($this->data as $performance)
    		{
    			$current_value = $performance["statistic"];
    
    			if ($current_value !== $last_value)
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
                
    			$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a>");
    			$player->SetCssClass("player");
    			$player->AddAttribute("typeof", "schema:Person");
    			$player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["player_url"]);
                $fields[] = $player;
                
    			if ($this->display_team)
    			{
    				$team = new XhtmlCell(false, $performance["team_name"]);
    				$team->SetCssClass("team");
                    $fields[] = $team;
    			}
                
                if (array_key_exists("total_matches", $performance)) {
        			$matches = new XhtmlCell(false, $performance["total_matches"]);
        			$matches->SetCssClass("numeric");
                    $fields[] = $matches;
                }
                
    			$aggregate = new XhtmlCell(false, $current_value);
    			$aggregate->SetCssClass("numeric");
                $fields[] = $aggregate;

				$row = new XhtmlRow($fields);

    			$this->AddRow($row);
    		}
		}
		parent::OnPreRender();
	}
}
?>
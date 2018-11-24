<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of player performances in batting, bowling and fielding
 * @author Rick
 *
 */
class PlayerPerformanceTable extends XhtmlTable
{
    private $performance_data;
    private $first_result;
    private $show_position;

    public function __construct($caption, array $performance_data, $first_result = 1, $show_position=false)
    {
        $this->performance_data = $performance_data;
        $this->first_result = (int)$first_result;
        $this->show_position = (bool)$show_position;

        parent::__construct();

        if (count($performance_data) > 0) 
        {
            $performance = $performance_data[0];
    
            # Create the table header
            $this->SetCaption($caption);
            $this->SetCssClass('player-performances');
            $fields = array();
            
            if ($show_position) {
                $position = new XhtmlCell(true, "#");
                $position->SetCssClass("position");
                $fields[] = $position;
            }

            $player = new XhtmlCell(true, "Player");
            $player->SetCssClass("player");
            $fields[] = $player;
            
            $match = new XhtmlCell(true, "Match");
            $fields[] = $match;
            
            $when = new XhtmlCell(true, "When");
            $fields[] = $when;

            if (array_key_exists("runs_scored", $performance))
            {
                $runs = new XhtmlCell(true, "Batting");
                $runs->SetCssClass("numeric");
                $fields[] = $runs;
            }

            if (array_key_exists("wickets", $performance))
            {
                $bowling = new XhtmlCell(true, "Bowling");
                $bowling->SetCssClass("numeric");
                $fields[] = $bowling;
            }
            
            if (array_key_exists("catches", $performance))
            {
                $catches = new XhtmlCell(true, "Catches");
                $catches->SetCssClass("numeric");
                $fields[] = $catches;
            }
            
            if (array_key_exists("run_outs", $performance))
            {
                $runouts = new XhtmlCell(true, "Run-outs");
                $runouts->SetCssClass("numeric");
                $fields[] = $runouts;
            }

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
            $current_value = "";
            if (array_key_exists("runs_scored", $performance))
            {
                $current_batting_value = $performance["runs_scored"];

                $not_out = ($performance["how_out"] == Batting::NOT_OUT or $performance["how_out"] == Batting::RETIRED or $performance["how_out"] == Batting::RETIRED_HURT);
                if ($not_out)
                {
                    $current_batting_value .= "*";
                }
                
                $current_value .= $current_batting_value;
            }

            if (array_key_exists("wickets", $performance))
            {
                $current_bowling_value = $performance["wickets"] . "/". $performance["runs_conceded"];
                $current_value .= $current_bowling_value;
            }
            
            if (array_key_exists("catches", $performance))
            {
                $current_value .= $performance["catches"];
            }
            
            if (array_key_exists("run_outs", $performance))
            {
                $current_value .= $performance["run_outs"];
            }
            
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
            if ($this->show_position){
                $position = new XhtmlCell(false, $pos);
                $position->SetCssClass("position");
                $fields[] = $position;        
            }

            $player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a>");
            $player->SetCssClass("player");
            $player->AddAttribute("typeof", "schema:Person");
            $player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["player_url"]);
            $fields[] = $player;
            
            $match = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["match_url"] . '">' . $performance["match_title"] . "</a>");
            $match->AddAttribute("typeof", "schema:SportsEvent");
            $match->AddAttribute("about", "https://www.stoolball.org.uk/id/match" . $performance["match_url"]);
            $match->SetCssClass("match");
            $fields[] = $match;
            
            $date = new XhtmlCell(false, Date::BritishDate($performance["match_time"], false, true, true));
            $date->SetCssClass("date");
            $fields[] = $date;

            if (array_key_exists("runs_scored", $performance))
            {
                $runs = new XhtmlCell(false, $current_batting_value == "" ? "&#8211;" : $current_batting_value);
                $runs->SetCssClass("numeric");
                if (!$not_out) $runs->AddCssClass("out");
                $fields[] = $runs;
            }
                        
            if (array_key_exists("wickets", $performance))
            {
                $bowling = new XhtmlCell(false, $current_bowling_value == "/" ? "&#8211;" : $current_bowling_value);
                $bowling->SetCssClass("numeric");
                $fields[] = $bowling;
            }
            
            if (array_key_exists("catches", $performance))
            {
                $catches = new XhtmlCell(false, $performance["catches"] == "0" ? "&#8211;" : $performance["catches"]);
                $catches->SetCssClass("numeric");
                $fields[] = $catches;
            }

            if (array_key_exists("run_outs", $performance))
            {
                $runouts = new XhtmlCell(false, $performance["run_outs"] == "0" ? "&#8211;" : $performance["run_outs"]);
                $runouts->SetCssClass("numeric");
                $fields[] = $runouts;
            }

            $row = new XhtmlRow($fields);
            $this->AddRow($row);
        }

        parent::OnPreRender();
    }
}
?>
<?php
require_once 'xhtml/tables/xhtml-table.class.php';

/**
 * Table of player of the match nominations
 * @author Rick
 *
 */
class PlayerOfTheMatchTable extends XhtmlTable
{
    private $performance_data;
    private $first_result;
    private $max_results;

    /**
     * Create a BattingInningsTable
     * @param $performance_data Batting[]
     * @param $display_team bool
     * @param int $first_result
     * @param int $max_results
     * @return void
     */
    public function __construct($performance_data, $first_result = 1, $max_results = null)
    {
        $this->performance_data = $performance_data;
        $this->first_result = (int)$first_result;
        if (!is_null($max_results)) $this->max_results = (int)$max_results;

        parent::XhtmlTable();

        # Create the table header
        $this->SetCaption("Player of the match nominations, most recent first");
        $this->SetCssClass('player-of-match');
        $player = new XhtmlCell(true, "Player");
        $player->SetCssClass("player");
        $match = new XhtmlCell(true, "Match");
        $when = new XhtmlCell(true, "When");
        $runs = new XhtmlCell(true, "Batting");
        $runs->SetCssClass("numeric");
        $bowling = new XhtmlCell(true, "Bowling");
        $bowling->SetCssClass("numeric");
        $catches = new XhtmlCell(true, "Catches");
        $catches->SetCssClass("numeric");
        $runouts = new XhtmlCell(true, "Run-outs");
        $runouts->SetCssClass("numeric");
        $header = new XhtmlRow(array($player, $match, $when, $runs, $bowling, $catches, $runouts));
        $header->SetIsHeader(true);
        $this->AddRow($header);
    }

    public function OnPreRender()
    {
        $i = $this->first_result;
        $last_value = "";
        foreach ($this->performance_data as $performance)
        {
            $current_batting_value = $performance["runs_scored"];
            $not_out = ($performance["how_out"] == Batting::NOT_OUT or $performance["how_out"] == Batting::RETIRED or $performance["how_out"] == Batting::RETIRED_HURT);
            if ($not_out)
            {
                $current_batting_value .= "*";
            }

            $current_bowling_value = $performance["wickets"] . "/". $performance["runs_conceded"];
            
            $current_value = $current_batting_value . $current_bowling_value . $performance["catches"];
            if ($current_value != $last_value)
            {
                # If 11th value is not same as 10th, stop. This can happen because DB query sees 40* and 40 as equal, but they're not.
                if (!is_null($this->max_results) and $i > $this->max_results) break;

                $pos = $i;
                $last_value = $current_value;
            }
            else
            {
                $pos = "=";
            }
            $i++;

            $player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["player_url"] . '">' . $performance["player_name"] . "</a>");
            $player->SetCssClass("player");
            $player->AddAttribute("typeof", "schema:Person");
            $player->AddAttribute("about", "http://www.stoolball.org.uk/id/player" . $performance["player_url"]);
            $match = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . $performance["match_url"] . '">' . $performance["match_title"] . "</a>");
            $match->AddAttribute("typeof", "schema:SportsEvent");
            $match->AddAttribute("about", "https://www.stoolball.org.uk/id/match" . $performance["match_url"]);
            $match->SetCssClass("match");
            $date = new XhtmlCell(false, Date::BritishDate($performance["match_time"], false, true, true));
            $date->SetCssClass("date");
            $runs = new XhtmlCell(false, $current_batting_value == "" ? "&#8211;" : $current_batting_value);
            $runs->SetCssClass("numeric");
            if (!$not_out) $runs->AddCssClass("out");
            $bowling = new XhtmlCell(false, $current_bowling_value == "/" ? "&#8211;" : $current_bowling_value);
            $bowling->SetCssClass("numeric");
            $catches = new XhtmlCell(false, $performance["catches"] == "0" ? "&#8211;" : $performance["catches"]);
            $catches->SetCssClass("numeric");
            $runouts = new XhtmlCell(false, $performance["run-outs"] == "0" ? "&#8211;" : $performance["run-outs"]);
            $runouts->SetCssClass("numeric");

            $row = new XhtmlRow(array($player, $match, $date, $runs, $bowling, $catches, $runouts));
            $this->AddRow($row);
        }

        parent::OnPreRender();
    }
}
?>
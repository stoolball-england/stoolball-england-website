<?php
require_once("xhtml/forms/xhtml-form.class.php");
require_once("xhtml/forms/xhtml-select.class.php");

/**
 * Form for users to filter player statistics
 * @author Rick
 *
 */
class StatisticsFilterControl extends XhtmlForm
{
    private $match_type_filter;
    private $player_type_filter;
	private $team_filter;
	private $opposition_filter;
	private $ground_filter;
	private $competition_filter;
	private $batting_position_filter;
	private $after_date_filter;
	private $before_date_filter;
    private $innings_filter;
    private $match_result_filter;

	/**
	 * Creates a new StatisticsFilterControl
	 */
	public function __construct()
	{
		parent::XhtmlForm();
		$this->AddAttribute("method", "get");
	}

    /**
     * Set the available match types, and the selected match type, from StatisticsFilter
     * @param mixed[] $match_type_filter
     */
    public function SupportMatchTypeFilter($match_type_filter)
    {
        $this->match_type_filter = is_array($match_type_filter) ? $match_type_filter : null;
    }

    /**
     * Set the available player types, and the selected player type, from StatisticsFilter
     * @param mixed[] $player_type_filter
     */
    public function SupportPlayerTypeFilter($player_type_filter)
    {
        $this->player_type_filter = is_array($player_type_filter) ? $player_type_filter : null;
    }

	/**
	 * Set the available teams, and the selected team, from StatisticsFilter
	 * @param mixed[] $team_filter
	 */
	public function SupportTeamFilter($team_filter)
	{
		$this->team_filter = is_array($team_filter) ? $team_filter : null;
	}

	/**
	 * Set the available opposition teams, and the selected opposition team, from StatisticsFilter
	 * @param mixed[] $opposition_filter
	 */
	public function SupportOppositionFilter($opposition_filter)
	{
		$this->opposition_filter = is_array($opposition_filter) ? $opposition_filter : null;
	}

	/**
	 * Set the available grounds, and the selected ground, from StatisticsFilter
	 * @param mixed[] $ground_filter
	 */
	public function SupportGroundFilter($ground_filter)
	{
		$this->ground_filter = is_array($ground_filter) ? $ground_filter : null;
	}

	/**
	 * Set the available competitions, and the selected competition, from StatisticsFilter
	 * @param mixed[] $competition_filter
	 */
	public function SupportCompetitionFilter($competition_filter)
	{
		$this->competition_filter = is_array($competition_filter) ? $competition_filter : null;
	}

	/**
	 * Set the available batting positions, and the selected batting position, from StatisticsFilter
	 * @param mixed[] $batting_position_filter
	 */
	public function SupportBattingPositionFilter($batting_position_filter)
	{
		$this->batting_position_filter = is_array($batting_position_filter) ? $batting_position_filter : null;
	}

	/**
	 * Set the start date applied as a filter
	 * @param int $date_filter_applied
	 */
	public function SupportAfterDateFilter($date_filter_applied)
	{
		$this->after_date_filter = (int)$date_filter_applied;
	}

	/**
	 * Set the end date applied as a filter
	 * @param int $date_filter_applied
	 */
	public function SupportBeforeDateFilter($date_filter_applied)
	{
		$this->before_date_filter = (int)$date_filter_applied;
	}
    
    /**
     * Set the innings applied as a filter
     * @param int $innings_filter_applied
     */
    public function SupportInningsFilter($innings_filter_applied)
    {
        $this->innings_filter = $innings_filter_applied;
    }
    
    
    /**
     * Set the match result applied as a filter
     * @param int $match_result_filter_applied
     */
    public function SupportMatchResultFilter($match_result_filter_applied)
    {
        $this->match_result_filter = $match_result_filter_applied;
    }

	/**
	 * Build the form
	 */
	protected function OnPreRender()
	{
		$this->AddControl('<div id="statisticsFilter" class="panel"><div><div><h2><span><span><span>Filter these statistics</span></span></span></h2>');

		# Track whether to show or hide this filter
		$filter = '<input type="hidden" id="filter" name="filter" value="';
		$filter .= (isset($_GET["filter"]) and $_GET["filter"] == "1") ? 1 : 0;
		$filter  .= '" />';
		$this->AddControl($filter);

        
        $type_filters = array();
        
        # Support player type filter
        if (is_array($this->player_type_filter))
        {
            require_once("stoolball/player-type.enum.php");
            $player_type_list = new XhtmlSelect("player-type");
            $blank = new XhtmlOption("any players", "");
            $player_type_list->AddControl($blank);
            foreach ($this->player_type_filter[0] as $player_type)
            {
                $player_type_list->AddControl(new XhtmlOption(strtolower(PlayerType::Text($player_type)), $player_type, $player_type === $this->player_type_filter[1]));
            }
            $label = new XhtmlElement("label", "Type of players", "aural");
            $label->AddAttribute("for", $player_type_list->GetXhtmlId());
            $type_filters[] = $label;
            $type_filters[] = $player_type_list;
        }
        
        # Support match type filter
        if (is_array($this->match_type_filter))
        {
            require_once("stoolball/match-type.enum.php");
            $match_type_list = new XhtmlSelect("match-type");
            $blank = new XhtmlOption("any match", "");
            $match_type_list->AddControl($blank);
            foreach ($this->match_type_filter[0] as $match_type)
            {
                $match_type_list->AddControl(new XhtmlOption(str_replace(" match", "", MatchType::Text($match_type)), $match_type, $match_type === $this->match_type_filter[1]));
            }
            $label = new XhtmlElement("label", "Type of competition", "aural");
            $label->AddAttribute("for", $match_type_list->GetXhtmlId());
            $type_filters[] = $label;
            $type_filters[] = $match_type_list;
        }
        
        if (count($type_filters)) {
            $type = new XhtmlElement("fieldset", new XhtmlElement("legend", "Match type", "formLabel"), "formPart");
            $controls = new XhtmlElement("div", null, "formControl");
            foreach($type_filters as $control) {
                $controls->AddControl($control);
            }
            $type->AddControl($controls);
            $this->AddControl($type);
        }

		# Support team filter
		if (is_array($this->team_filter))
		{
			$team_list = new XhtmlSelect("team");
			$blank = new XhtmlOption("any team", "");
			$team_list->AddControl($blank);
			foreach ($this->team_filter[0] as $team)
			{
				$team_list->AddControl(new XhtmlOption($team->GetName(), $team->GetId(), $team->GetId() == $this->team_filter[1]));
			}
			$this->AddControl(new FormPart("Team", $team_list));
		}

		# Support opposition team filter
		if (is_array($this->opposition_filter))
		{
			$opposition_list = new XhtmlSelect("opposition");
			$blank = new XhtmlOption("any team", "");
			$opposition_list->AddControl($blank);
			foreach ($this->opposition_filter[0] as $team)
			{
				$opposition_list->AddControl(new XhtmlOption($team->GetName(), $team->GetId(), $team->GetId() == $this->opposition_filter[1]));
			}
			$this->AddControl(new FormPart("Against", $opposition_list));
		}

		# Support ground filter
		if (is_array($this->ground_filter))
		{
			$ground_list = new XhtmlSelect("ground");
			$blank = new XhtmlOption("any ground", "");
			$ground_list->AddControl($blank);
			foreach ($this->ground_filter[0] as $ground)
			{
				$ground_list->AddControl(new XhtmlOption($ground->GetNameAndTown(), $ground->GetId(), $ground->GetId() == $this->ground_filter[1]));
			}
			$this->AddControl(new FormPart("Ground", $ground_list));
		}

		# Support competition filter
		if (is_array($this->competition_filter))
		{
			$competition_list = new XhtmlSelect("competition");
			$blank = new XhtmlOption("any competition", "");
			$competition_list->AddControl($blank);
			foreach ($this->competition_filter[0] as $competition)
			{
				$competition_list->AddControl(new XhtmlOption($competition->GetName(), $competition->GetId(), $competition->GetId() == $this->competition_filter[1]));
			}
			$this->AddControl(new FormPart("Competition", $competition_list));
		}

		# Support date filter
		# Use text fields rather than HTML 5 date fields because then we can accept dates like "last Tuesday"
		# which PHP will understand. Jquery calendar is at least  as good as native date pickers anyway.
		$date_fields = '<div class="formPart">
			<label for="from" class="formLabel">From date</label>
			<div class="formControl twoFields">
			<input type="text" class="date firstField" id="from" name="from" placeholder="any date" autocomplete="off"';
		if (!$this->IsValid()) $date_fields .= ' value="' . (isset($_GET["from"]) and is_string($_GET['from']) ? $_GET["from"] : "") . '"'; # GET values already escaped, so no XSS risk
		else if ($this->after_date_filter) $date_fields .= ' value="' . Date::BritishDate($this->after_date_filter, false, true, false) . '"';
		$date_fields .= ' />
			<label for="to">Up to date
			<input type="text" class="date" id="to" name="to" placeholder="any date" autocomplete="off"';
		if (!$this->IsValid()) $date_fields .= ' value="' . (isset($_GET["to"]) and is_string($_GET['to']) ? $_GET["to"] : "") . '"'; # GET values already escaped, so no XSS risk
		else if ($this->before_date_filter) $date_fields .= ' value="' . Date::BritishDate($this->before_date_filter, false, true, false) . '"';
		$date_fields .= ' /></label>
			</div>
			</div>';
		$this->AddControl($date_fields);

        # Support innings filter
        $innings_list = new XhtmlSelect("innings");
        $innings_list->AddControl(new XhtmlOption("either innings", ""));
        $innings_list->AddControl(new XhtmlOption("first innings", 1, 1 == $this->innings_filter));
        $innings_list->AddControl(new XhtmlOption("second innings", 2, 2 == $this->innings_filter));
        $this->AddControl('<div class="formPart">
        <label for="innings" class="formLabel">Innings</label>
        <div class="formControl">' . $innings_list . '</div>' .
        '</div>');
        
		# Support batting position filter
		if (is_array($this->batting_position_filter))
		{
			$batting_position_list = new XhtmlSelect("batpos");
			$blank = new XhtmlOption("any position", "");
			$batting_position_list->AddControl($blank);
			foreach ($this->batting_position_filter[0] as $batting_position)
			{
				$batting_position_list->AddControl(new XhtmlOption(($batting_position == 1 ? "opening" : $batting_position), $batting_position, $batting_position == $this->batting_position_filter[1]));
			}
			$this->AddControl('<div class="formPart">
			<label for="batpos" class="formLabel">Batting at</label>
			<div class="formControl">' . $batting_position_list . '</div>' .
			'</div>');
		}        

        # Support match result filter
        $result_list = new XhtmlSelect("result");
        $result_list->AddControl(new XhtmlOption("any result", ""));
        $result_list->AddControl(new XhtmlOption("win", 1, 1 === $this->match_result_filter));
        $result_list->AddControl(new XhtmlOption("tie", 0, 0 === $this->match_result_filter));
        $result_list->AddControl(new XhtmlOption("lose", -1, -1 === $this->match_result_filter));
        $this->AddControl('<div class="formPart">
        <label for="result" class="formLabel">Match result</label>
        <div class="formControl">' . $result_list . '</div>' .
        '</div>');

		# Preserve the player filter if applied
		if (isset($_GET["player"]) and is_numeric($_GET["player"]))
		{
			$this->AddControl('<input type="hidden" name="player" id="player" value="' . $_GET["player"] . '" />');
		}

		$this->AddControl('<p class="actions"><input type="submit" value="Apply filter" /></p>');
		$this->AddControl('</div></div></div>');
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		# Only validate fields that the user will type in. Invalid query strings will simply be ignored.
		require_once('data/validation/date-validator.class.php');
		require_once('data/validation/not-future-validator.class.php');
		$this->AddValidator(new DateValidator("from", "We didn't recognise the 'from date' you typed"));
		$this->AddValidator(new NotFutureValidator("from", "Sorry, we haven't got any statistics from the future"));
		$this->AddValidator(new DateValidator("to", "We didn't recognise the 'up to date' you typed"));
	}
}
?>
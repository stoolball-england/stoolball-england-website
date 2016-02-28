<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');

class ScorecardEditControl extends DataEditControl
{
	private $b_user_is_match_admin = false;
	private $b_user_is_match_owner = false;
	const DATA_SEPARATOR = "|";

	public function __construct(SiteSettings $settings)
	{
		# set up element
		$this->SetDataObjectClass('Match');
		parent::__construct($settings);
		$this->SetAllowCancel(true);
		$this->SetButtonText('Next &raquo;');

		# check permissions
		$this->b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$match = $this->BuildPostedScorecard();
		$this->SetDataObject($match);
	}

	/**
	 * Builds data posted on pages 2/3 back into a match object
	 * @return Match
	 */
	private function BuildPostedScorecard()
	{
		$match = new Match($this->GetSettings());
		$match->SetId($this->GetDataObjectId());

		# Must have data on which team is which, otherwise none of the rest makes sense
		# Team ids are essential for saving data, while team names and match title are
		# purely so they can be redisplayed if the page is invalid
		$key = "teams";
		if (!isset($_POST[$key]) or (strpos($_POST[$key], ScorecardEditControl::DATA_SEPARATOR) === false)) return $match;

		$teams = explode(ScorecardEditControl::DATA_SEPARATOR,$_POST[$key],6);
		if (count($teams) != 6) return $match;

		switch ($teams[0])
		{
			case "0":
				$match->Result()->SetHomeBattedFirst(false);
				$home_batting = ($this->GetCurrentPage() == 3);
				break;
			case "1":
				$match->Result()->SetHomeBattedFirst(true);
				$home_batting = ($this->GetCurrentPage() == 2);
				break;
			default:
				$home_batting = ($this->GetCurrentPage() == 2);
		}

		$home_team = new Team($this->GetSettings());
		$home_team->SetId($teams[1]);
		$home_team->SetName($teams[2]);
		$match->SetHomeTeam($home_team);

		$away_team = new Team($this->GetSettings());
		$away_team->SetId($teams[3]);
		$away_team->SetName($teams[4]);
		$match->SetAwayTeam($away_team);

		$match->SetTitle($teams[5]);

		# Read posted batting data
		$i = 1;
		$key = "batName$i";
		while (isset($_POST[$key]))
		{
			# This will increment up to the number previously posted, which means the same number of boxes
			# will be redisplayed on an invalid postback
			$match->SetMaximumPlayersPerTeam($i);

			# The row exists - has it been filled in?
			if (trim($_POST[$key]))
			{
				# Read the batter data in this row
				$player = new Player($this->GetSettings());
				$player->SetName($_POST[$key]);
				$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

				$key = "batHowOut$i";
				$how_out = (isset($_POST[$key]) and is_numeric($_POST[$key])) ? (int)$_POST[$key] : null;

				$key = "batOutBy$i";
				$dismissed_by = null;
				if (isset($_POST[$key]) and trim($_POST[$key]))
				{
					$dismissed_by = new Player($this->GetSettings());
					$dismissed_by->SetName($_POST[$key]);
					$dismissed_by->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());
				}

				$key = "batBowledBy$i";
				$bowler = null;
				if (isset($_POST[$key]) and trim($_POST[$key]))
				{
					$bowler = new Player($this->GetSettings());
					$bowler->SetName($_POST[$key]);
					$bowler->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());
				}

				# Correct caught and bowled if marked as caught
				if ($how_out == Batting::CAUGHT and !is_null($dismissed_by) and !is_null($bowler) and trim($dismissed_by->GetName()) == trim($bowler->GetName()))
				{
					$how_out = Batting::CAUGHT_AND_BOWLED;
					$dismissed_by = null;
				}

				$key = "batRuns$i";
				$runs = (isset($_POST[$key]) and is_numeric($_POST[$key])) ? (int)$_POST[$key] : null;

                $key = "batBalls$i";
                $balls = (isset($_POST[$key]) and is_numeric($_POST[$key])) ? (int)$_POST[$key] : null;

				# Add that batting performance to the match result
				$batting = new Batting($player, $how_out, $dismissed_by, $bowler, $runs, $balls);
				if ($home_batting)
				{
					$match->Result()->HomeBatting()->Add($batting);
				}
				else
				{
					$match->Result()->AwayBatting()->Add($batting);
				}
			}

			# Get ready to check for another row
			$i++;
			$key = "batName$i";
		}

		$key = "batByes";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::BYES);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batWides";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::WIDES);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batNoBalls";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::NO_BALLS);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batBonus";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			$player = new Player($this->GetSettings());
			$player->SetPlayerRole(Player::BONUS_RUNS);
			$player->Team()->SetId($home_batting ? $home_team->GetId() : $away_team->GetId());

			$batting = new Batting($player, null, null, null, (int)$_POST[$key]);
			if ($home_batting)
			{
				$match->Result()->HomeBatting()->Add($batting);
			}
			else
			{
				$match->Result()->AwayBatting()->Add($batting);
			}
		}

		$key = "batTotal";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			if ($home_batting)
			{
				$match->Result()->SetHomeRuns($_POST[$key]);
			}
			else
			{
				$match->Result()->SetAwayRuns($_POST[$key]);
			}
		}

		$key = "batWickets";
		if (isset($_POST[$key]) and is_numeric($_POST[$key]))
		{
			if ($home_batting)
			{
				$match->Result()->SetHomeWickets($_POST[$key]);
			}
			else
			{
				$match->Result()->SetAwayWickets($_POST[$key]);
			}
		}

		# Read posted bowling data
		$i = 1;
		$key = "bowlerName$i";
		while (isset($_POST[$key]))
		{
			# This will increment up to the number previously posted, which means the same number of boxes
			# will be redisplayed on an invalid postback
			$match->SetOvers($i);

			# The row exists - has it been filled in?
			if (trim($_POST[$key]))
			{
				# Read the bowler data in this row
				# strlen test allows 0 but not empty string, because is_numeric allows empty string
				$player = new Player($this->GetSettings());
				$player->SetName($_POST[$key]);
				$player->Team()->SetId($home_batting ? $away_team->GetId() : $home_team->GetId());

				$key = "bowlerBalls$i";
				$balls = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerNoBalls$i";
				$no_balls = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerWides$i";
				$wides = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				$key = "bowlerRuns$i";
				$runs = (isset($_POST[$key]) and is_numeric($_POST[$key]) and strlen(trim($_POST[$key]))) ? (int)$_POST[$key] : null;

				# Add that over to the match result
				$bowling = new Over($player);
				$bowling->SetBalls($balls);
				$bowling->SetNoBalls($no_balls);
				$bowling->SetWides($wides);
				$bowling->SetRunsInOver($runs);
				if ($home_batting)
				{
					$match->Result()->AwayOvers()->Add($bowling);
				}
				else
				{
					$match->Result()->HomeOvers()->Add($bowling);
				}
			}

			# Get ready to check for another row
			$i++;
			$key = "bowlerName$i";
		}

		return $match;
	}

	function CreateControls()
	{
		$match = $this->GetDataObject();
		/* @var $match Match */
		$this->b_user_is_match_owner = ($match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $match->GetAddedBy()->GetId());

		switch($this->GetCurrentPage())
		{
			case ScorecardEditControl::FIRST_INNINGS:
				if ($match->Result()->GetHomeBattedFirst() === false)
				{
					$this->CreateScorecardControls($match, $match->GetAwayTeam(), $match->Result()->AwayBatting(), $match->GetHomeTeam(), $match->Result()->HomeOvers(), $match->Result()->GetAwayRuns(), $match->Result()->GetAwayWickets());
				}
				else
				{
					$this->CreateScorecardControls($match, $match->GetHomeTeam(), $match->Result()->HomeBatting(), $match->GetAwayTeam(), $match->Result()->AwayOvers(), $match->Result()->GetHomeRuns(), $match->Result()->GetHomeWickets());
				}
				break;

			case ScorecardEditControl::SECOND_INNINGS:
				if ($match->Result()->GetHomeBattedFirst() === false)
				{
					$this->CreateScorecardControls($match, $match->GetHomeTeam(), $match->Result()->HomeBatting(), $match->GetAwayTeam(), $match->Result()->AwayOvers(), $match->Result()->GetHomeRuns(), $match->Result()->GetHomeWickets());
				}
				else
				{
					$this->CreateScorecardControls($match, $match->GetAwayTeam(), $match->Result()->AwayBatting(), $match->GetHomeTeam(), $match->Result()->HomeOvers(), $match->Result()->GetAwayRuns(), $match->Result()->GetAwayWickets());
				}
				break;
		}
	}

	/**
	 * Sets up controls for pages 2/3 of the wizard
	 * @param Match $match
	 * @param Team $batting_team
	 * @param Collection $batting_data
	 * @param Team $bowling_team
	 * @param Collection $bowling_data
	 * @param int $total
	 * @param int $wickets_taken
	 * @return void
	 */
	private function CreateScorecardControls(Match $match, Team $batting_team, Collection $batting_data, Team $bowling_team, Collection $bowling_data, $total, $wickets_taken)
	{
		require_once("xhtml/tables/xhtml-table.class.php");

		$batting_table = new XhtmlTable();
		$batting_table->SetCaption($batting_team->GetName() . "'s batting");
		$batting_table->SetCssClass("scorecard scorecardEditor batting");
        
		$out_by_header = new XhtmlCell(true, '<span class="small">Fielder</span><span class="large">Caught<span class="wrapping-hair-space"> </span>/<span class="wrapping-hair-space"> </span><span class="nowrap">run-out by</span></span>');
		$out_by_header->SetCssClass("dismissedBy");
        $bowler_header = new XhtmlCell(true, "Bowler");
        $bowler_header->SetCssClass("bowler");
        $score_header = new XhtmlCell(true, "Runs");
        $score_header->SetCssClass("numeric");
        $balls_header = new XhtmlCell(true, "Balls");
        $balls_header->SetCssClass("numeric");
		$batting_headings = new XhtmlRow(array("Batsman", "How out", $out_by_header, $bowler_header, $score_header, $balls_header));
		$batting_headings->SetIsHeader(true);
		$batting_table->AddRow($batting_headings);

		$batting_data->ResetCounter();
		$byes = null;
		$wides = null;
		$no_balls = null;
		$bonus = null;

		# Loop = max players + 4, because if you have a full scorecard you have to keep looping to get the 4 extras players
		for ($i=1; $i <= $match->GetMaximumPlayersPerTeam()+4; $i++)
		{
			$batting = ($batting_data->MoveNext()) ? $batting_data->GetItem() : null;
			/* @var $batting Batting */

			# Grab the scores for extras players to use later
			if (!is_null($batting))
			{
				switch($batting->GetPlayer()->GetPlayerRole())
				{
					case Player::BYES:
						$byes = $batting->GetRuns();
						break;
					case Player::WIDES:
						$wides = $batting->GetRuns();
						break;
					case Player::NO_BALLS:
						$no_balls = $batting->GetRuns();
						break;
					case Player::BONUS_RUNS:
						$bonus = $batting->GetRuns();
						break;
				}
			}

			# Don't write a table row for the last four loops, we'll do that next because they're different
			if ($i <= $match->GetMaximumPlayersPerTeam())
			{
				$player = new TextBox("batName$i", (is_null($batting) or !$batting->GetPlayer()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetPlayer()->GetName(), $this->IsValidSubmit());
				$player->SetMaxLength(100);
				$player->AddAttribute("autocomplete", "off");
				$player->AddCssClass("player team" . $batting_team->GetId());

				$how = new XhtmlSelect("batHowOut$i", null, $this->IsValidSubmit());
				$how->SetCssClass("howOut");
				$how->AddOptions(array(Batting::DID_NOT_BAT => Batting::Text(Batting::DID_NOT_BAT),
				Batting::NOT_OUT => Batting::Text(Batting::NOT_OUT),
				Batting::CAUGHT => Batting::Text(Batting::CAUGHT),
				Batting::BOWLED => Batting::Text(Batting::BOWLED),
				Batting::CAUGHT_AND_BOWLED => str_replace(" and ", "/", Batting::Text(Batting::CAUGHT_AND_BOWLED)), # use shorter version to minimise width of dropdown and allow more room for names
				Batting::RUN_OUT => Batting::Text(Batting::RUN_OUT),
				Batting::BODY_BEFORE_WICKET => "bbw", # use shorter version to minimise width of dropdown and allow more room for names
				Batting::HIT_BALL_TWICE => Batting::Text(Batting::HIT_BALL_TWICE),
				Batting::TIMED_OUT => Batting::Text(Batting::TIMED_OUT),
				Batting::RETIRED_HURT => Batting::Text(Batting::RETIRED_HURT),
				Batting::RETIRED => Batting::Text(Batting::RETIRED),
				Batting::UNKNOWN_DISMISSAL => Batting::Text(Batting::UNKNOWN_DISMISSAL)) , null);
				if (!is_null($batting) and $batting->GetPlayer()->GetPlayerRole() == Player::PLAYER and $this->IsValidSubmit()) $how->SelectOption($batting->GetHowOut());

				$out_by = new TextBox("batOutBy$i", (is_null($batting) or is_null($batting->GetDismissedBy()) or !$batting->GetDismissedBy()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetDismissedBy()->GetName(), $this->IsValidSubmit());
				$out_by->SetMaxLength(100);
				$out_by->AddAttribute("autocomplete", "off");
				$out_by->AddCssClass("player team" . $bowling_team->GetId());

				$bowled_by = new TextBox("batBowledBy$i", (is_null($batting) or is_null($batting->GetBowler()) or !$batting->GetBowler()->GetPlayerRole() == Player::PLAYER) ? "" : $batting->GetBowler()->GetName(), $this->IsValidSubmit());
				$bowled_by->SetMaxLength(100);
				$bowled_by->AddAttribute("autocomplete", "off");
				$bowled_by->AddCssClass("player team" . $bowling_team->GetId());

				$runs = new TextBox("batRuns$i", (is_null($batting) or $batting->GetPlayer()->GetPlayerRole() != Player::PLAYER) ? "" : $batting->GetRuns(), $this->IsValidSubmit());
				$runs->SetCssClass("numeric runs");
                $runs->AddAttribute("type", "number");
                $runs->AddAttribute("min", "0");
				$runs->AddAttribute("autocomplete", "off");

                $balls = new TextBox("batBalls$i", (is_null($batting) or $batting->GetPlayer()->GetPlayerRole() != Player::PLAYER) ? "" : $batting->GetBallsFaced(), $this->IsValidSubmit());
                $balls->SetCssClass("numeric balls");
                $balls->AddAttribute("type", "number");
                $balls->AddAttribute("min", "0");
                $balls->AddAttribute("autocomplete", "off");

				$batting_row = new XhtmlRow(array($player, $how, $out_by, $bowled_by, $runs, $balls));
                $batting_row->GetFirstCell()->SetCssClass("batsman");
                $batting_table->AddRow($batting_row);
			}
		}

		$batting_table->AddRow($this->CreateExtrasRow("batByes", "Byes", "extras", "numeric runs", $byes));
		$batting_table->AddRow($this->CreateExtrasRow("batWides", "Wides", "extras", "numeric runs", $wides));
		$batting_table->AddRow($this->CreateExtrasRow("batNoBalls", "No balls", "extras", "numeric runs", $no_balls));
		$batting_table->AddRow($this->CreateExtrasRow("batBonus", "Bonus or penalty runs", "extras", "numeric runs", $bonus));
		$batting_table->AddRow($this->CreateExtrasRow("batTotal", "Total", "totals", "numeric", $total));
		$batting_table->AddRow($this->CreateWicketsRow($match, $wickets_taken));

		$this->AddControl($batting_table);

		$bowling_table = new XhtmlTable();
		$bowling_table->SetCaption($bowling_team->GetName() . "'s bowling, over-by-over");
		$bowling_table->SetCssClass("scorecard scorecardEditor bowling-scorecard bowling");

		$over_header = new XhtmlCell(true, 'Balls bowled <span class="qualifier">(excluding extras)</span>');
		$over_header->SetCssClass("numeric balls");
        $wides_header = new XhtmlCell(true, "Wides");
        $wides_header->SetCssClass("numeric");
		$no_balls_header = new XhtmlCell(true, "No balls");
		$no_balls_header->SetCssClass("numeric");
		$runs_header = new XhtmlCell(true, "Over total");
		$runs_header->SetCssClass("numeric");
		$bowling_headings = new XhtmlRow(array("Bowler", $over_header, $wides_header, $no_balls_header, $runs_header));
		$bowling_headings->SetIsHeader(true);
		$bowling_table->AddRow($bowling_headings);

		$bowling_data->ResetCounter();
		for ($i=1; $i <= $match->GetOvers(); $i++)
		{
			$bowling = ($bowling_data->MoveNext()) ? $bowling_data->GetItem() : null;
			/* @var $bowling Over */

			$blank_row = (is_null($bowling) or is_null($bowling->GetOverNumber())); // don't list records generated from batting card to record wickets taken

			$player = new TextBox("bowlerName$i", $blank_row ? "" : $bowling->GetPlayer()->GetName(), $this->IsValidSubmit());
			$player->SetMaxLength(100);
			$player->AddAttribute("autocomplete", "off");
			$player->AddCssClass("player team" . $bowling_team->GetId());

			$balls = new TextBox("bowlerBalls$i", $blank_row ? "" : $bowling->GetBalls(), $this->IsValidSubmit());
			$balls->AddAttribute("autocomplete", "off");
			$balls->AddAttribute("type", "number");
            $balls->AddAttribute("min", "0");
            $balls->AddAttribute("max", "10");
            $balls->SetCssClass("numeric balls");

            $wides = new TextBox("bowlerWides$i", $blank_row ? "" : $bowling->GetWides(), $this->IsValidSubmit());
            $wides->AddAttribute("autocomplete", "off");
            $wides->AddAttribute("type", "number");
            $wides->AddAttribute("min", "0");
            $wides->SetCssClass("numeric wides");

			$no_balls = new TextBox("bowlerNoBalls$i", $blank_row ? "" : $bowling->GetNoBalls(), $this->IsValidSubmit());
			$no_balls->AddAttribute("autocomplete", "off");
            $no_balls->AddAttribute("type", "number");
            $no_balls->AddAttribute("min", "0");
            $no_balls->SetCssClass("numeric no-balls");

			$runs = new TextBox("bowlerRuns$i", $blank_row ? "" : $bowling->GetRunsInOver(), $this->IsValidSubmit());
			$runs->AddAttribute("autocomplete", "off");
            $runs->AddAttribute("type", "number");
            $runs->AddAttribute("min", "0");
            $runs->SetCssClass("numeric runs");

			$bowling_row = new XhtmlRow(array($player, $balls, $wides, $no_balls, $runs));
			$bowling_row->GetFirstCell()->SetCssClass("bowler");
			$bowling_table->AddRow($bowling_row);
		}

		$this->AddControl($bowling_table);

        		
        if ($match->GetLastAudit() != null)
        {
            require_once("data/audit-control.class.php");
            $this->AddControl(new AuditControl($match->GetLastAudit(), "match"));
        }

		$home_batted_first = "";
		if (!is_null($match->Result()->GetHomeBattedFirst())) $home_batted_first = (int)$match->Result()->GetHomeBattedFirst();

		$teams = new TextBox("teams", $home_batted_first . ScorecardEditControl::DATA_SEPARATOR .
		$match->GetHomeTeamId() . ScorecardEditControl::DATA_SEPARATOR .
		$match->GetHomeTeam()->GetName() . ScorecardEditControl::DATA_SEPARATOR .
		$match->GetAwayTeamId() . ScorecardEditControl::DATA_SEPARATOR .
		$match->GetAwayTeam()->GetName() . ScorecardEditControl::DATA_SEPARATOR .
		$match->GetTitle());
		$teams->SetMode(TextBoxMode::Hidden());
		$this->AddControl($teams);
	}

	/**
	 * Creates an extras/totals row at the bottom of the batting card
	 * @param string $id
	 * @param string $label
	 * @param string $row_class
	 * @param string $box_class
	 * @param int $value
	 * @return void
	 */
	private function CreateExtrasRow($id, $label, $row_class, $box_class, $value)
	{
		$extras_header = new XhtmlCell(true, $label);
		$extras_header->SetColumnSpan(4);
		$extras = new TextBox($id, $value, $this->IsValidSubmit());
		$extras->AddAttribute("autocomplete", "off");
		$extras->SetCssClass($box_class);
        $extras->AddAttribute("type", "number");
        $balls_column = new XhtmlCell(false, null);
		$extras_row = new XhtmlRow(array($extras_header, $extras, $balls_column));
		$extras_row->SetCssClass($row_class);
		return $extras_row;
	}

    private function CreateWicketsRow(Match $match, $wickets_taken) 
    {
        $wickets_header = new XhtmlCell(true, "Wickets");
        $wickets_header->SetColumnSpan(4);
        $wickets = new XhtmlSelect("batWickets", null, $this->IsValidSubmit());
        $wickets->SetBlankFirst(true);

        $max_wickets = $match->GetMaximumPlayersPerTeam()-2;
        $season_dates = Season::SeasonDates($match->GetStartTime()); # working with GMT
        if (Date::Year($season_dates[0]) != Date::Year($season_dates[1]))
        {
            # outdoor needs maximum-2, but indoor needs maximum-1 cos last batter can play on.
            # if there's any chance it's indoor use maximum-1
            $max_wickets = $match->GetMaximumPlayersPerTeam()-1;
        }
        for ($i = 0; $i <= $max_wickets; $i++) $wickets->AddControl(new XhtmlOption($i));

        $wickets->AddControl(new XhtmlOption('all out', -1));
        if ($this->IsValidSubmit() and !is_null($wickets_taken)) $wickets->SelectOption($wickets_taken);

        $balls_column = new XhtmlCell(false, null);
        $wickets_row = new XhtmlRow(array($wickets_header, $wickets, $balls_column));
        $wickets_row->SetCssClass("totals");
        
        return $wickets_row;
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once("data/validation/requires-other-fields-validator.class.php");
		require_once('data/validation/numeric-range-validator.class.php');

		# Add per-row batting validators
		$i = 1;
		$key = "batName$i";
		while (isset($_POST[$key]))
		{
			switch ($i % 10)
			{
				case 1:
					$ordinal = $i . "st";
					break;
				case 2:
					$ordinal = $i . "nd";
					break;
				case 3:
					$ordinal = $i . "rd";
					break;
				default:
					$ordinal = $i . "th";
			}
			$this->AddValidator(new LengthValidator("batName$i", "The $ordinal batsman's name must be 100 characters or fewer", 0, 100));
			$this->AddValidator(new LengthValidator("batOutBy$i", "Who caught/ran-out the $ordinal batsman must be 100 characters or fewer", 0, 100));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batOutBy$i", "batName$i"),"You've said who caught/ran out the $ordinal batsman. Please name the batsman."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batOutBy$i", "batHowOut$i"),"You've said who caught/ran out the $ordinal batsman, but they weren't caught or run-out.", array(array(Batting::CAUGHT, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT))));
			$this->AddValidator(new LengthValidator("batBowledBy$i", "Who bowled the $ordinal batsman must be 100 characters or fewer", 0, 100));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batName$i"),"You've said who bowled to the $ordinal batsman. Please name the batsman."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batHowOut$i"),"You've said who bowled to the $ordinal batsman, but they were 'not out' or didn't bat.", array(array(Batting::CAUGHT, Batting::BOWLED, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT, Batting::BODY_BEFORE_WICKET, Batting::HIT_BALL_TWICE, Batting::RETIRED, Batting::RETIRED_HURT))));
			$this->AddValidator(new NumericValidator("batHowOut$i", "How the $ordinal batsman was out should be a number. For example: '3' for 'bowled'."));
			$this->AddValidator(new NumericValidator("batRuns$i", "The $ordinal batsman's runs should be in figures. For example: '50', not 'fifty'."));
            $this->AddValidator(new NumericValidator("batBalls$i", "The $ordinal batsman's balls faced should be in figures. For example: '50', not 'fifty'."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batRuns$i", "batName$i"),"You've added runs for the $ordinal batsman. Please name the batsman."));
            $this->AddValidator(new RequiresOtherFieldsValidator(array("batBalls$i", "batName$i"),"You've added balls faced for the $ordinal batsman. Please name the batsman."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("batBowledBy$i", "batHowOut$i"),"You've added runs for the $ordinal batsman, but they were 'not out' or didn't bat.", array(array(Batting::CAUGHT, Batting::BOWLED, Batting::CAUGHT_AND_BOWLED, Batting::RUN_OUT, Batting::BODY_BEFORE_WICKET, Batting::HIT_BALL_TWICE, Batting::RETIRED, Batting::RETIRED_HURT))));

			# Get ready to check for another row
			$i++;
			$key = "batName$i";
		}

		# Add remaining batting validators
		$this->AddValidator(new NumericValidator('batByes', 'Byes should be in figures. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator('batWides', 'Wides should be in figures. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator('batNoBalls', 'No balls should be in figures. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator('batBonus', 'Bonus runs should be in figures. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator('batTotal', 'The total score should be in figures. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator('batWickets', 'Wickets for the innings should be in figures. For example: \'5\', not \'five\'.'));

		# Add per-row bowling validators
		$i = 1;
		$key = "bowlerName$i";
		while (isset($_POST[$key]))
		{
			switch ($i % 10)
			{
				case 1:
					$ordinal = ($i == 11) ? $i . "th" : $i . "st";
					break;
				case 2:
					$ordinal = ($i == 12) ? $i . "th" : $i . "nd";
					break;
				case 3:
					$ordinal = ($i == 13) ? $i . "th" : $i . "rd";
					break;
				default:
					$ordinal = $i . "th";
			}
			$this->AddValidator(new LengthValidator("bowlerName$i", "The $ordinal bowler's name must be 100 characters or fewer", 0, 100));
			$this->AddValidator(new NumericValidator("bowlerBalls$i", "The $ordinal bowler's balls bowled should be in figures. For example: '5', not 'five'."));
			$this->AddValidator(new NumericRangeValidator("bowlerBalls$i", "The $ordinal bowler's balls bowled should be between 1 and 10", 1, 10));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerBalls$i", "bowlerName$i"),"You've added balls bowled for the $ordinal bowler. Please name the bowler."));
			$this->AddValidator(new NumericValidator("bowlerNoBalls$i", "The $ordinal bowler's no balls should be in figures. For example: '5', not 'five'."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerNoBalls$i", "bowlerName$i"),"You've added no balls for the $ordinal bowler. Please name the bowler."));
			$this->AddValidator(new NumericValidator("bowlerWides$i", "The $ordinal bowler's wides should be in figures. For example: '5', not 'five'."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerWides$i", "bowlerName$i"),"You've added wides for the $ordinal bowler. Please name the bowler."));
			$this->AddValidator(new NumericValidator("bowlerRuns$i", "The $ordinal bowler's runs should be in figures. For example: '50', not 'fifty'."));
			$this->AddValidator(new RequiresOtherFieldsValidator(array("bowlerRuns$i", "bowlerName$i"),"You've added runs for the $ordinal bowler. Please name the bowler."));

			# Get ready to check for another row
			$i++;
			$key = "bowlerName$i";
		}
	}

	/**
	 * Scorecard for first innings
	 * @var int
	 */
	const FIRST_INNINGS = 2;

	/**
	 * Scorecard for second innings
	 * @var int
	 */
	const SECOND_INNINGS = 3;
}
?>
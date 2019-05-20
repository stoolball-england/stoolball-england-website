<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('data/date.class.php');

class MatchResultEditControl extends DataEditControl
{
	private $b_in_section;

	/**
	 * Creates a new MatchResultEditControl
	 * @param SiteSettings $settings
	 * @param string $csrf_token
	 * @return void
	 */
	public function __construct(SiteSettings $settings, $csrf_token)
	{
		$this->SetDataObjectClass('Match');
		parent::__construct($settings, $csrf_token, false);
	}

	/**
	 * Sets whether this edit control is within a repeated section
	 *
	 * @param bool $b_in_section
	 */
	public function SetIsInSection($b_in_section)
	{
		$this->b_in_section = (bool)$b_in_section;
	}

	/**
	 * Gets whether this edit control is within a repeated section
	 *
	 * @return bool
	 */
	public function GetIsInSection()
	{
		return $this->b_in_section;
	}


	/**
	 * The heading, where {0} becomes the match title, and {1} the match date
	 *
	 * @var string
	 */
	private $s_heading = '{0}, {1}';

	/**
	 * Sets the heading, where {0} becomes the match title, and {1} the match date
	 *
	 * @param string $s_heading
	 * @return void
	 */
	public function SetHeading($s_heading)
	{
		$this->s_heading = (string)$s_heading;
	}

	/**
	 * Gets the heading
	 *
	 * @return string
	 */
	public function GetHeading()
	{
		return $this->s_heading;
	}

	/**
	 * whether to show the match title
	 *
	 * @var bool
	 */
	private $b_show_heading = true;

	/**
	 * Sets the whether to show the match title
	 *
	 * @param bool $b_show
	 * @return void
	 */
	public function SetShowHeading($b_show)
	{
		$this->b_show_heading = (bool)$b_show;
	}

	/**
	 * Gets the whether to show the match title
	 *
	 * @return bool
	 */
	public function GetShowHeading()
	{
		return $this->b_show_heading;
	}

	/**
	 * whether to show the match date in validation errors
	 *
	 * @var bool
	 */
	private $b_show_date_in_validation = true;

	/**
	 * Sets the whether to show the match date in validation errors
	 *
	 * @param bool $b_show
	 * @return void
	 */
	public function SetShowDateInValidationErrors($b_show)
	{
		$this->b_show_date_in_validation = (bool)$b_show;
	}

	/**
	 * Gets the whether to show the match date in validation errors
	 *
	 * @return bool
	 */
	public function GetShowDateInValidationErrors()
	{
		return $this->b_show_date_in_validation;
	}

	/**
	 * Builds a match object containing the result information posted by the control
	 *
	 */
	public function BuildPostedDataObject()
	{
		$o_match = new Match($this->GetSettings());
		$o_match->SetId($this->GetDataObjectId());

		# Get match date
		$s_key = $this->GetNamingPrefix() . 'Date';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$o_match->SetStartTime($_POST[$s_key]);
		}

		# Get team names
		$s_key = $this->GetNamingPrefix() . 'Home';
		if (isset($_POST[$s_key]))
		{
			$team_data = explode(";", $_POST[$s_key], 2);
			if (count($team_data) == 2)
			{
				$o_home = new Team($this->GetSettings());
				$o_home->SetId($team_data[0]);
				$o_home->SetName($team_data[1]);
				$o_match->SetHomeTeam($o_home);
			}
		}

		$s_key = $this->GetNamingPrefix() . 'Away';
		if (isset($_POST[$s_key]))
		{
			$team_data = explode(";", $_POST[$s_key], 2);
			if (count($team_data) == 2)
			{
				$o_away = new Team($this->GetSettings());
				$o_away->SetId($team_data[0]);
				$o_away->SetName($team_data[1]);
				$o_match->SetAwayTeam($o_away);
			}
		}

		# Get who batted first
		$s_key = $this->GetNamingPrefix() . 'BatFirst';
		if (isset($_POST[$s_key]))
		{
			$s_batted = $_POST[$s_key];
			if ($s_batted == 'home')
			{
				$o_match->Result()->SetHomeBattedFirst(true);
			}
			else if ($s_batted == 'away')
			{
				$o_match->Result()->SetHomeBattedFirst(false);
			}
		}

		# Get the result
		$s_key = $this->GetNamingPrefix() . 'Result';
		if (isset($_POST[$s_key]))
		{
			$s_result = $_POST[$s_key];
			if (strlen($s_result))
			{
				$o_match->Result()->SetResultType($s_result);
			}
		}

		# Get the home score
		$s_key = $this->GetNamingPrefix() . 'HomeRuns';
		if (isset($_POST[$s_key]))
		{
			$s_home_runs = $_POST[$s_key];
			if (strlen($s_home_runs))
			{
				$o_match->Result()->SetHomeRuns($s_home_runs);
			}
		}

		$s_key = $this->GetNamingPrefix() . 'HomeWickets';
		if (isset($_POST[$s_key]))
		{
			$s_home_wickets = $_POST[$s_key];
			if (strlen($s_home_wickets))
			{
				$o_match->Result()->SetHomeWickets($s_home_wickets);
			}
		}

		# Get the away score
		$s_key = $this->GetNamingPrefix() . 'AwayRuns';
		if (isset($_POST[$s_key]))
		{
			$s_away_runs = $_POST[$s_key];
			if (strlen($s_away_runs))
			{
				$o_match->Result()->SetAwayRuns($s_away_runs);
			}
		}

		$s_key = $this->GetNamingPrefix() . 'AwayWickets';
		if (isset($_POST[$s_key]))
		{
			$s_away_wickets = $_POST[$s_key];
			if (strlen($s_away_wickets))
			{
				$o_match->Result()->SetAwayWickets($s_away_wickets);
			}
		}

		$s_key = $this->GetNamingPrefix() . 'Comments';
		if (isset($_POST[$s_key]))
		{
			$o_match->SetNewComment($_POST[$s_key]);
		}

		$this->SetDataObject($o_match);
	}

	protected function CreateControls()
	{
		$o_match = $this->GetDataObject();

		/* @var $o_match Match */

		$b_got_teams = !(is_null($o_match->GetHomeTeam()) || is_null($o_match->GetAwayTeam()));
		$b_future = ($o_match->GetId() and $o_match->GetStartTime() > gmdate('U'));

		// Move CSS class to div element
		$o_match_outer_1 = new XhtmlElement('div');
		if ($this->GetCssClass())
		{
			$o_match_outer_1->SetCssClass('matchResultEdit ' . $this->GetCssClass());
		}
		else
		{
			$o_match_outer_1->SetCssClass('matchResultEdit');
		}
		$this->SetCssClass('');

		$o_match_outer_2 = new XhtmlElement('div');
		$o_match_box = new XhtmlElement('div');
		$this->AddControl($o_match_outer_1);
		$o_match_outer_1->AddControl($o_match_outer_2);
		$o_match_outer_2->AddControl($o_match_box);

		if ($this->GetShowHeading())
		{
			$s_heading = str_replace('{0}', $o_match->GetTitle(), $this->GetHeading());
			$s_heading = str_replace('{1}', Date::BritishDate($o_match->GetStartTime(), false), $s_heading);

			$o_title_inner_1 = new XhtmlElement('span',  htmlentities($s_heading, ENT_QUOTES, "UTF-8", false));
			$o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
			$o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
			$o_heading = new XhtmlElement($this->b_in_section ? 'h3' : 'h2', $o_title_inner_3);
			$o_match_box->AddControl($o_heading);
		}

		# Who's playing?
		$o_home_name = new TextBox($this->GetNamingPrefix() . 'Home');
		$o_away_name = new TextBox($this->GetNamingPrefix() . 'Away');
		$o_home_name->SetMode(TextBoxMode::Hidden());
		$o_away_name->SetMode(TextBoxMode::Hidden());
		if (!is_null($o_match->GetHomeTeam()))
		{
			$o_home_name->SetText($o_match->GetHomeTeam()->GetId() . ";" . $o_match->GetHomeTeam()->GetName());
		}
		if (!is_null($o_match->GetAwayTeam()))
		{
			$o_away_name->SetText($o_match->GetAwayTeam()->GetId() . ";" . $o_match->GetAwayTeam()->GetName());
		}
		$this->AddControl($o_home_name);
		$this->AddControl($o_away_name);

		# When? (for validator message only)
		$when = new TextBox($this->GetNamingPrefix() . 'Date', $o_match->GetStartTime());
		$when->SetMode(TextBoxMode::Hidden());
		$this->AddControl($when);

		# Who batted first?
		if (!$b_future)
		{
			$o_bat_first = new XhtmlSelect($this->GetNamingPrefix() . 'BatFirst');
			$o_bat_first->AddControl(new XhtmlOption("Don't know", ''));
			if ($b_got_teams)
			{
				$o_bat_first->AddControl(new XhtmlOption($o_match->GetHomeTeam()->GetName(), 'home'));
				$o_bat_first->AddControl(new XhtmlOption($o_match->GetAwayTeam()->GetName(), 'away'));
			}
			else
			{
				$o_bat_first->AddControl(new XhtmlOption('Home team', 'home'));
				$o_bat_first->AddControl(new XhtmlOption('Away team', 'away'));
			}
			if (!is_null($o_match->Result()->GetHomeBattedFirst()))
			{
				if ($o_match->Result()->GetHomeBattedFirst())
				{
					$o_bat_first->SelectOption('home');
				}
				else
				{
					$o_bat_first->SelectOption('away');
				}
			}
			$o_bat_part = new FormPart('Who batted first?', $o_bat_first);
			$o_match_box->AddControl($o_bat_part);
		}

		# Who won?
		$o_winner = new XhtmlSelect($this->GetNamingPrefix() . 'Result');
		$o_winner->AddControl(new XhtmlOption($b_future ? 'The match will go ahead' : "Don't know", ''));

		$result_types = array(MatchResult::HOME_WIN, MatchResult::AWAY_WIN, MatchResult::HOME_WIN_BY_FORFEIT, MatchResult::AWAY_WIN_BY_FORFEIT, MatchResult::TIE, MatchResult::POSTPONED, MatchResult::CANCELLED, MatchResult::ABANDONED);
        if ($o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
        {
            # You don't postpone a match in a tournament for another day
            unset ($result_types[array_search(MatchResult::POSTPONED, $result_types, true)]);
        } 
		foreach ($result_types as $result_type)
		{
			if (!$b_future or MatchResult::PossibleInAdvance($result_type))
			{
				if ($b_got_teams)
				{
					$o_winner->AddControl(new XhtmlOption($this->NameTeams(MatchResult::Text($result_type), $o_match->GetHomeTeam(), $o_match->GetAwayTeam()), $result_type));
				}
				else
				{
					$o_winner->AddControl(new XhtmlOption(MatchResult::Text($result_type), $result_type));
				}
			}
		}
		$o_winner->SelectOption($o_match->Result()->GetResultType());
		$o_win_part = new FormPart($b_future ? 'Will it happen?' : 'Who won?', $o_winner);
		$o_match_box->AddControl($o_win_part);

		# If the match has already happened
		if (!$b_future)
		{
			# What did each team score?
			$this->CreateTeamScoreControls($o_match_box, 'Home', $o_match, $o_match->GetHomeTeam(), $o_match->Result()->GetHomeRuns(), $o_match->Result()->GetHomeWickets());
			$this->CreateTeamScoreControls($o_match_box, 'Away', $o_match, $o_match->GetAwayTeam(), $o_match->Result()->GetAwayRuns(), $o_match->Result()->GetAwayWickets());

			# Any comments?
			$comments = new TextBox($this->GetNamingPrefix() . 'Comments', '', $this->IsValidSubmit());
			$comments->SetMode(TextBoxMode::MultiLine());
			$comments->AddAttribute('class', 'matchReport');

			$comments_label = new XhtmlElement('label');
			$comments_label->AddAttribute('for', $comments->GetXhtmlId());
			$comments_label->AddCssClass('matchReport');
			$comments_label->AddControl('Add a match report:');

			$o_match_box->AddControl($comments_label);
			$o_match_box->AddControl($comments);
		}


		# Show audit data
		if ($o_match->GetLastAudit() != null)
		{
            require_once("data/audit-control.class.php");
			$o_match_box->AddControl(new AuditControl($o_match->GetLastAudit(), "match"));
        }

	}

	/**
	 * Adds controls to edit a team score
	 *
	 * @param XhtmlElement $o_container
	 * @param string $s_id_prefix
	 * @param Match $o_match
	 * @param Team $o_team
	 * @param int $i_runs
	 * @param int $i_wickets
	 */
	private function CreateTeamScoreControls($o_container, $s_team_role, Match $o_match, Team $o_team=null, $i_runs, $i_wickets)
	{
		$o_box = new XhtmlElement('div');

		$o_runs = new TextBox($this->GetNamingPrefix() . $s_team_role . 'Runs', $i_runs);
		$o_runs->SetCssClass("numeric");
		if ($i_runs == null) $o_runs->PopulateData();
		$s_runs_label = (is_null($o_team) ? $s_team_role : $o_team->GetName()) . ' score';
		$o_part = new FormPart($s_runs_label, $o_box);
		$o_box->AddControl($o_runs);
		$o_part->GetLabel()->AddAttribute('for', $o_runs->GetXhtmlId());

		$o_wickets = new XhtmlSelect($this->GetNamingPrefix() . $s_team_role . 'Wickets', ' for ');
		$o_wickets->SetBlankFirst(true);

		$max_wickets = $o_match->GetMaximumPlayersPerTeam()-2;
		$season_dates = Season::SeasonDates($o_match->GetStartTime()); # working with GMT
		if (Date::Year($season_dates[0]) != Date::Year($season_dates[1]))
		{
			# outdoor needs maximum-2, but indoor needs maximum-1 cos last batter can play on.
			# if there's any chance it's indoor use maximum-1
			$max_wickets = $o_match->GetMaximumPlayersPerTeam()-1;
		}
		for ($i = 0; $i <= $max_wickets; $i++) $o_wickets->AddControl(new XhtmlOption($i));

		$o_wickets->AddControl(new XhtmlOption('all out', -1));
		$o_wickets->SelectOption($i_wickets);
		$o_box->AddControl($o_wickets);

		$o_container->AddControl($o_part);
	}

	/**
	 * Replace the words "home" and "away" with the names of the relevant teams
	 *
	 * @param string $s_text
	 * @param Team $o_home
	 * @param Team $o_away
	 * @return string
	 */
	private function NameTeams($s_text, Team $o_home, Team $o_away)
	{
		$s_text = str_ireplace('Home', $o_home->GetName(), $s_text);
		$s_text = str_ireplace('Away', $o_away->GetName(), $s_text);
		return $s_text;
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/required-field-validator.class.php');
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');

		$o_match = $this->GetDataObject();
		/* @var $o_match Match */

		$s_match_date = $this->GetShowDateInValidationErrors() ? ' for ' . Date::BritishDate($o_match->GetStartTime(), false) : '';
		$s_home_team = (is_object($o_match) and is_object($o_match->GetHomeTeam())) ? $o_match->GetHomeTeam()->GetName() : 'home team';
		$s_away_team = (is_object($o_match) and is_object($o_match->GetAwayTeam())) ? $o_match->GetAwayTeam()->GetName() : 'away team';

		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'HomeRuns', 'The ' . $s_home_team . ' score' . $s_match_date . ' should be a number. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'HomeWickets', 'The ' . $s_home_team . ' wickets' . $s_match_date . ' should be a number. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'AwayRuns', 'The ' . $s_away_team . ' score' . $s_match_date . ' should be a number. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'AwayWickets', 'The ' . $s_away_team . ' wickets' . $s_match_date . ' should be a number. For example: \'5\', not \'five\'.'));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Result', 'The result identifier' . $s_match_date . ' should be a number'));
	}
}
?>
<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');

class MatchHighlightsEditControl extends DataEditControl
{
	private $b_user_is_match_admin = false;
	private $b_user_is_match_owner = false;
	const DATA_SEPARATOR = "|";

	/**
	 * Creates a new MatchHighlightsEditControl
	 * @param SiteSettings $settings
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, $csrf_token)
	{
		# set up element
		$this->SetDataObjectClass('Match');
		parent::__construct($settings, $csrf_token);
		$this->SetAllowCancel(true);

		# check permissions
		$this->b_user_is_match_admin = AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		$match = new Match($this->GetSettings());
		$match->SetId($this->GetDataObjectId());

		# Get match date
		$s_key = $this->GetNamingPrefix() . 'Date';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and is_numeric($_POST[$s_key]))
		{
			$match->SetStartTime($_POST[$s_key]);
		}

		# Get team names
		$s_key = $this->GetNamingPrefix() . 'Home';
		if (isset($_POST[$s_key]))
		{
			$team_data = explode(MatchHighlightsEditControl::DATA_SEPARATOR, $_POST[$s_key], 2);
			if (count($team_data) == 2)
			{
				$o_home = new Team($this->GetSettings());
				$o_home->SetId($team_data[0]);
				$o_home->SetName($team_data[1]);
				$match->SetHomeTeam($o_home);
			}
		}

		$s_key = $this->GetNamingPrefix() . 'Away';
		if (isset($_POST[$s_key]))
		{
			$team_data = explode(MatchHighlightsEditControl::DATA_SEPARATOR, $_POST[$s_key], 2);
			if (count($team_data) == 2)
			{
				$o_away = new Team($this->GetSettings());
				$o_away->SetId($team_data[0]);
				$o_away->SetName($team_data[1]);
				$match->SetAwayTeam($o_away);
			}
		}

		# Get the result
		$s_key = $this->GetNamingPrefix() . 'Result';
		if (isset($_POST[$s_key]))
		{
			$s_result = $_POST[$s_key];
			if (strlen($s_result))
			{
				$match->Result()->SetResultType($s_result);
			}
		}

		# Get players of the match. Fields to use depend on which radio button was selected.
		$s_key = $this->GetNamingPrefix() . 'POM';
		if (isset($_POST[$s_key]))
		{
			$pom_type = (int)$_POST[$s_key];

			if ($pom_type == MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_OVERALL)
			{
				$s_key = $this->GetNamingPrefix() . 'Player';
				if (isset($_POST[$s_key]) and $_POST[$s_key])
				{
					$player = new Player($this->GetSettings());
					$player->SetName($_POST[$s_key]);

					$s_key = $this->GetNamingPrefix() . 'PlayerTeam';
					if (isset($_POST[$s_key]))
					{
						$player->Team()->SetId($_POST[$s_key]);
					}

					$match->Result()->SetPlayerOfTheMatch($player);
				}


			}
			else if ($pom_type == MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_HOME_AND_AWAY)
			{
				$s_key = $this->GetNamingPrefix() . 'PlayerHome';
				if (isset($_POST[$s_key]) and $_POST[$s_key])
				{
					$player = new Player($this->GetSettings());
					$player->SetName($_POST[$s_key]);
					$player->Team()->SetId($match->GetHomeTeamId());
					$match->Result()->SetPlayerOfTheMatchHome($player);
				}

				$s_key = $this->GetNamingPrefix() . 'PlayerAway';
				if (isset($_POST[$s_key]) and $_POST[$s_key])
				{
					$player = new Player($this->GetSettings());
					$player->SetName($_POST[$s_key]);
					$player->Team()->SetId($match->GetAwayTeamId());
					$match->Result()->SetPlayerOfTheMatchAway($player);
				}

			}
		}

		$s_key = $this->GetNamingPrefix() . 'Comments';
		if (isset($_POST[$s_key]))
		{
			$match->SetNewComment($_POST[$s_key]);
		}

        $this->SetDataObject($match);
	}

	function CreateControls()
	{
		$match = $this->GetDataObject();
		/* @var $match Match */
		$this->b_user_is_match_owner = ($match->GetAddedBy() instanceof User and AuthenticationManager::GetUser()->GetId() == $match->GetAddedBy()->GetId());

		$this->CreateHighlightsControls($match);
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
	 * Sets up controls for page 4 of the wizard
	 * @param Match $match
	 * @return void
	 */
	private function CreateHighlightsControls(Match $match)
	{
		$b_got_teams = !(is_null($match->GetHomeTeam()) || is_null($match->GetAwayTeam()));

		// Move CSS class to div element
		$match_outer_1 = new XhtmlElement('div');
		$match_outer_1->SetCssClass('matchResultEdit panel');

		$match_outer_2 = new XhtmlElement('div');
		$match_box = new XhtmlElement('div');
		$this->AddControl($match_outer_1);
		$match_outer_1->AddControl($match_outer_2);
		$match_outer_2->AddControl($match_box);

		$o_title_inner_1 = new XhtmlElement('span',  "Match highlights");
		$o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
		$o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
		$o_heading = new XhtmlElement('h2', $o_title_inner_3);
		$match_box->AddControl($o_heading);

		# Who's playing?
		$o_home_name = new TextBox($this->GetNamingPrefix() . 'Home');
		$o_away_name = new TextBox($this->GetNamingPrefix() . 'Away');
		$o_home_name->SetMode(TextBoxMode::Hidden());
		$o_away_name->SetMode(TextBoxMode::Hidden());
		if (!is_null($match->GetHomeTeam()))
		{
			$o_home_name->SetText($match->GetHomeTeam()->GetId() . MatchHighlightsEditControl::DATA_SEPARATOR . $match->GetHomeTeam()->GetName());
		}
		if (!is_null($match->GetAwayTeam()))
		{
			$o_away_name->SetText($match->GetAwayTeam()->GetId() . MatchHighlightsEditControl::DATA_SEPARATOR . $match->GetAwayTeam()->GetName());
		}
		$this->AddControl($o_home_name);
		$this->AddControl($o_away_name);

		# When? (for validator message only)
		$when = new TextBox($this->GetNamingPrefix() . 'Date', $match->GetStartTime());
		$when->SetMode(TextBoxMode::Hidden());
		$this->AddControl($when);

		# Who won?
		$o_winner = new XhtmlSelect($this->GetNamingPrefix() . 'Result');
		$o_winner->AddControl(new XhtmlOption("Don't know", ''));

		$result_types = array(MatchResult::HOME_WIN, MatchResult::AWAY_WIN, MatchResult::TIE, MatchResult::ABANDONED);
		foreach ($result_types as $result_type)
		{
			if ($b_got_teams)
			{
				$o_winner->AddControl(new XhtmlOption($this->NameTeams(MatchResult::Text($result_type), $match->GetHomeTeam(), $match->GetAwayTeam()), $result_type));
			}
			else
			{
				$o_winner->AddControl(new XhtmlOption(MatchResult::Text($result_type), $result_type));
			}
		}
		if ($this->IsValidSubmit())
		{
			if ($match->Result()->GetResultType() == MatchResult::UNKNOWN and !is_null($match->Result()->GetHomeRuns()) and !is_null($match->Result()->GetAwayRuns()))
			{
				# If match result is not known but we can guess from the entered scores, select it
				if ($match->Result()->GetHomeRuns() > $match->Result()->GetAwayRuns()) $o_winner->SelectOption(MatchResult::HOME_WIN);
				else if ($match->Result()->GetHomeRuns() < $match->Result()->GetAwayRuns()) $o_winner->SelectOption(MatchResult::AWAY_WIN);
				else if ($match->Result()->GetHomeRuns() == $match->Result()->GetAwayRuns()) $o_winner->SelectOption(MatchResult::TIE);
			}
			else
			{
				$o_winner->SelectOption($match->Result()->GetResultType());
			}
		}
		$o_win_part = new FormPart('Who won?', $o_winner);
		$match_box->AddControl($o_win_part);

		# Get current player of match
		$player = $match->Result()->GetPlayerOfTheMatch();
		$home_player = $match->Result()->GetPlayerOfTheMatchHome();
		$away_player = $match->Result()->GetPlayerOfTheMatchAway();

		$current_pom = MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_NONE;
		if ($player instanceof Player) $current_pom = MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_OVERALL;
		else if ($home_player instanceof Player or $away_player instanceof Player) $current_pom = MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_HOME_AND_AWAY;

		# Choose from different types of player of the match
		require_once('xhtml/forms/radio-button.class.php');

		$pom_container = new XhtmlElement('fieldset', new XhtmlElement('legend', 'Player of the match', 'formLabel'));
		$pom_container->SetCssClass('formPart');
		$pom_options = new XhtmlElement('div', null, 'formControl radioButtonList');
		$pom_options->SetXhtmlId($this->GetNamingPrefix() . "PlayerOptions");
		$pom_container->AddControl($pom_options);
		$match_box->AddControl($pom_container);

		$pom_options->AddControl(new RadioButton($this->GetNamingPrefix() . 'POM' . MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_NONE, $this->GetNamingPrefix() . 'POM', "none chosen", MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_NONE, $current_pom == MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_NONE, $this->IsValidSubmit()));
		$pom_options->AddControl(new RadioButton($this->GetNamingPrefix() . 'POM' . MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_OVERALL, $this->GetNamingPrefix() . 'POM', "yes, one chosen", MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_OVERALL, $current_pom == MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_OVERALL, $this->IsValidSubmit()));
		$pom_options->AddControl(new RadioButton($this->GetNamingPrefix() . 'POM' . MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_HOME_AND_AWAY, $this->GetNamingPrefix() . 'POM', "yes, one from each team", MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_HOME_AND_AWAY, $current_pom == MatchHighlightsEditControl::PLAYER_OF_THE_MATCH_HOME_AND_AWAY, $this->IsValidSubmit()));

		# Controls for entering a single player of the match
		$player_name = new TextBox($this->GetNamingPrefix() . 'Player', $player instanceof Player ? $player->GetName() : '', $this->IsValidSubmit());
		$player_name->SetMaxLength(100);
		$player_name->AddCssClass("player");
		$player_name->AddCssClass("team" . $match->GetHomeTeamId());
		$player_name->AddCssClass("team" . $match->GetAwayTeamId());
		$player_name->AddAttribute("autocomplete", "off");
		$player_box = new XhtmlElement("div", $player_name);

		$player_team = new XhtmlSelect($this->GetNamingPrefix() . "PlayerTeam", " playing for", $this->IsValidSubmit());
		$player_team->SetCssClass("playerTeam"); # for JS
		$player_team->AddControl(new XhtmlOption("Don't know", ""));
		$player_team->AddControl(new XhtmlOption($match->GetHomeTeam()->GetName(), $match->GetHomeTeamId()));
		$player_team->AddControl(new XhtmlOption($match->GetAwayTeam()->GetName(), $match->GetAwayTeamId()));
		if ($player instanceof Player) $player_team->SelectOption($player->Team()->GetId());
		$player_box->AddControl($player_team);

		$player_part = new FormPart("Player's name", $player_box);
		$player_part->SetXhtmlId($this->GetNamingPrefix() . "OnePlayer");
		$player_part->GetLabel()->AddAttribute("for", $player_name->GetXhtmlId());
		$match_box->AddControl($player_part);

		# Controls for entering home and away players of the match
		$home_box = new TextBox($this->GetNamingPrefix() . 'PlayerHome', $home_player instanceof Player ? $home_player->GetName() : '', $this->IsValidSubmit());
		$home_box->SetMaxLength(100);
		$home_box->AddCssClass("player");
		$home_box->AddCssClass("team" . $match->GetHomeTeamId());
		$home_box->AddAttribute("autocomplete", "off");
		$home_part = new FormPart($this->NameTeams('Home player', $match->GetHomeTeam(), $match->GetAwayTeam()), $home_box);
		$home_part->SetCssClass("formPart multiPlayer");
		$match_box->AddControl($home_part);

		$away_box = new TextBox($this->GetNamingPrefix() . 'PlayerAway', $away_player instanceof Player ? $away_player->GetName() : '', $this->IsValidSubmit());
		$away_box->SetMaxLength(100);
		$away_box->AddCssClass("player");
		$away_box->AddCssClass("team" . $match->GetAwayTeamId());
		$away_box->AddAttribute("autocomplete", "off");
		$away_part = new FormPart($this->NameTeams('Away player', $match->GetHomeTeam(), $match->GetAwayTeam()), $away_box);
		$away_part->SetCssClass("formPart multiPlayer");
		$match_box->AddControl($away_part);

		# Any comments?
		$comments = new TextBox($this->GetNamingPrefix() . 'Comments', '', $this->IsValidSubmit());
		$comments->SetMode(TextBoxMode::MultiLine());
		$comments->AddAttribute('class', 'matchReport');

		$comments_label = new XhtmlElement('label');
		$comments_label->AddAttribute('for', $comments->GetXhtmlId());
		$comments_label->AddCssClass('matchReport');
		$comments_label->AddControl('Add a match report:');

		$match_box->AddControl($comments_label);
		$match_box->AddControl($comments);

        if ($match->GetLastAudit() != null)
        {
            require_once("data/audit-control.class.php");
            $match_box->AddControl(new AuditControl($match->GetLastAudit(), "match"));
        }
        
		$this->SetButtonText('Save match');
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		require_once('data/validation/numeric-validator.class.php');
		require_once('data/validation/length-validator.class.php');
		require_once('data/validation/required-field-validator.class.php');

		$match = $this->GetDataObject();
		/* @var $match Match */

		$s_home_team = (is_object($match) and is_object($match->GetHomeTeam())) ? $match->GetHomeTeam()->GetName() : 'home team';
		$s_away_team = (is_object($match) and is_object($match->GetAwayTeam())) ? $match->GetAwayTeam()->GetName() : 'away team';

		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'Result', 'The result identifier must be a number'));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'Player', "The player of the match must be 100 characters or fewer.", 0, 100));
		$this->AddValidator(new NumericValidator($this->GetNamingPrefix() . 'PlayerTeam', "The player of the match's team must be identified by a number"));
		$this->AddValidator(new RequiredFieldValidator(array($this->GetNamingPrefix() . 'Player', $this->GetNamingPrefix() . 'PlayerTeam'), "Please enter both the name and team of the player of the match.", ValidatorMode::AllOrNothing()));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'PlayerHome', "The $s_home_team player of the match must be 100 characters or fewer.", 0, 100));
		$this->AddValidator(new LengthValidator($this->GetNamingPrefix() . 'PlayerAway', "The $s_away_team player of the match must be 100 characters or fewer.", 0, 100));
	}

	/**
	 * No player of the match is nominated
	 *
	 * @return int
	 */
	const PLAYER_OF_THE_MATCH_NONE = 0;

	/**
	 * One player is selected as player of the match
	 *
	 * @return int
	 */
	const PLAYER_OF_THE_MATCH_OVERALL = 1;

	/**
	 * One player from each team is nominated as the team's player of the match
	 *
	 * @return int
	 */
	const PLAYER_OF_THE_MATCH_HOME_AND_AWAY = 2;

}
?>
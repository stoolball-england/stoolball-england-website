<?php
require_once('data/data-edit-control.class.php');
require_once('data/related-id-editor.class.php');
require_once('stoolball/competition.class.php');
require_once('stoolball/points-adjustments-editor.class.php');
require_once('stoolball/teams-in-season-editor.class.php');
require_once('data/id-value.class.php');

class SeasonEditControl extends DataEditControl
{
	private $result_types;

	/**
	 * Aggregated editor for points adjustments
	 *
	 * @var PointsAdjustmentsEditor
	 */
	private $adjustments_editor;

	/**
	 * Aggregated editor for match types
	 *
	 * @var RelatedIdEditor
	 */
	private $match_types_editor;

	/**
	 * Aggregated editor for teams
	 *
	 * @var TeamsInSeasonEditor
	 */
	private $teams_editor;

	/**
	 * Tracks whether a new season is just saved, because SeasonManager does some extra work in that case
	 *
	 * @var bool
	 */
	private $b_saving_new = false;

	/**
	 * Creates a SeasonEditControl
	 *
	 * @param SiteSettings $o_settings
	 */
	public function __construct(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('Season');
		parent::__construct($o_settings);

		# set up aggregated editors
		$this->match_types_editor = new RelatedIdEditor($o_settings, $this, 'MatchType', 'Match types', array('Type of match'), 'IdValue', false, 'GetId', 'SetId', 'GetValue');
		$this->match_types_editor->SetMinimumItems(1);
		$this->match_types_editor->SetPossibleDataObjects(
		array(
		new IdValue(MatchType::CUP, ucfirst(MatchType::Text(MatchType::CUP))),
		new IdValue(MatchType::FRIENDLY, ucfirst(MatchType::Text(MatchType::FRIENDLY))),
		new IdValue(MatchType::LEAGUE, ucfirst(MatchType::Text(MatchType::LEAGUE))),
		new IdValue(MatchType::PRACTICE, ucfirst(MatchType::Text(MatchType::PRACTICE))),
		new IdValue(MatchType::TOURNAMENT, ucfirst(MatchType::Text(MatchType::TOURNAMENT))),
		)
		);
		#$this->match_types_editor->SetValuesToExclude(array(MatchType::TournamentMatch())); # Tournament match is implied by Tournament

		$this->adjustments_editor = new PointsAdjustmentsEditor($o_settings, $this, 'Points', 'Points adjustments', array('Points', 'Awarded or deducted', 'Team', 'Reason'));

		$this->teams_editor = new TeamsInSeasonEditor($o_settings, $this, 'Team', 'Teams', array('Team', 'Withdrawn'));

		# initialise arrays
		$this->result_types = array(
		new MatchResult(MatchResult::HOME_WIN),
		new MatchResult(MatchResult::AWAY_WIN),
		new MatchResult(MatchResult::HOME_WIN_BY_FORFEIT),
		new MatchResult(MatchResult::AWAY_WIN_BY_FORFEIT),
		new MatchResult(MatchResult::TIE),
		new MatchResult(MatchResult::POSTPONED),
		new MatchResult(MatchResult::CANCELLED),
		new MatchResult(MatchResult::ABANDONED)
		);
	}

	/**
	 * @return void
	 * @param Team[] $a_teams
	 * @desc Sets all the teams which could play in the season
	 */
	public function SetTeams($a_teams)
	{
		$this->teams_editor->SetTeams($a_teams);
	}

	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control is editing
	 */
	function BuildPostedDataObject()
	{
		/* @var $o_season Season */
		$o_season = new Season($this->GetSettings());
        if (isset($_POST['start']) and is_numeric($_POST['start'])) $o_season->SetStartYear($_POST['start']);
        $i_year_span = isset($_POST['when']) ? (int)$_POST['when'] : 0;
        $o_season->SetEndYear($o_season->GetStartYear() + $i_year_span);
        $o_season->SetName($o_season->GetYears());

		if (isset($_POST['item']))
		{
			$o_season->SetId($_POST['item']);
		}
		else
		{
			$this->b_saving_new = true;
			$this->match_types_editor->SetMinimumItems(0); # because will be populated from previous season (if there is one)
		}
		$o_season->SetIntro(ucfirst(trim($_POST['intro'])));
        $o_season->SetShortUrl($_POST[$this->GetNamingPrefix() . 'ShortUrl']);

		# Get the competition short URL and generate a season URL, rather than providing
		# a direct interface for season URLs.
		$o_comp = new Competition($this->GetSettings());
		$o_comp->SetId($_POST['competition']);
        $o_season->SetCompetition($o_comp);

		# Match types - get from aggregated editor
		$selected_match_types = $this->match_types_editor->DataObjects()->GetItems();
		foreach ($selected_match_types as $id_value) $o_season->MatchTypes()->Add($id_value->GetId());

		# Results and rules
		if (isset($_POST['results'])) $o_season->SetResults($_POST['results']);
		foreach ($this->result_types as $o_result)
		{
			/*@var $o_result MatchResult */
			$s_key = $this->GetNamingPrefix() . 'Result' . $o_result->GetResultType() . 'Home';
			if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and is_numeric($_POST[$s_key]))
			{
				$o_result->SetHomePoints($_POST[$s_key]);
			}

			$s_key = $this->GetNamingPrefix() . 'Result' . $o_result->GetResultType() . 'Away';
			if (isset($_POST[$s_key]) and strlen($_POST[$s_key]) and is_numeric($_POST[$s_key]))
			{
				$o_result->SetAwayPoints($_POST[$s_key]);
			}

			if (!is_null($o_result->GetHomePoints()) or !is_null($o_result->GetAwayPoints()))
			{
				$o_season->PossibleResults()->Add($o_result);
			}

		}

		# Points adjustments - get from aggregated editor
		$o_season->PointsAdjustments()->SetItems($this->adjustments_editor->DataObjects()->GetItems());

		# Show league table?
		$o_season->SetShowTable(isset($_POST['showTable']));
		$o_season->SetShowTableRunsScored(isset($_POST['runsScored']));
		$o_season->SetShowTableRunsConceded(isset($_POST['runsConceded']));

		# Teams - get from aggregated editor
		$a_teams_in_season = $this->teams_editor->DataObjects()->GetItems();
		foreach ($a_teams_in_season as $team_in_season)
		{
			/* @var $team_in_season TeamInSeason */
			$o_season->AddTeam($team_in_season->GetTeam());
			if ($team_in_season->GetWithdrawnFromLeague()) $o_season->TeamsWithdrawnFromLeague()->Add($team_in_season->GetTeam());
		}

		$this->SetDataObject($o_season);
	}


	function CreateControls()
	{
        $this->AddCssClass('legacy-form');

		/* @var $team Team */
		/* @var $comp Competition */
		$season = $this->GetDataObject();
		/* @var $season Season */

		require_once('xhtml/forms/form-part.class.php');
		require_once('xhtml/forms/textbox.class.php');
		require_once('xhtml/forms/radio-button.class.php');
		require_once('xhtml/forms/checkbox.class.php');

		# Show fewer options for a new season than when editing, beacause a new season gets settings copied from last year to save time
		$b_is_new_season = !(bool)$season->GetId();

		# Add competition
		$comp = $season->GetCompetition();
        
        $competition = new TextBox($this->GetNamingPrefix() . 'competition', $comp->GetId());
        $competition->SetMode(TextBoxMode::Hidden());
        $this->AddControl($competition);
                
        # Make current short URL available, because then it can match the suggested one and be left alone
        $short_url = new TextBox($this->GetNamingPrefix() . 'ShortUrl', $season->GetShortUrl());
        $short_url->SetMode(TextBoxMode::Hidden());
        $this->AddControl($short_url);
			
        # add years
		$start_box = new TextBox('start', is_null($season->GetStartYear()) ? Date::Year(gmdate('U')) : $season->GetStartYear(), $this->IsValidSubmit());
		$start_box->AddAttribute('maxlength', 4);
		$start = new FormPart('Year season starts', $start_box);
		$this->AddControl($start);

		$summer = new RadioButton('summer', 'when', 'Summer season', 0);
		$winter = new RadioButton('winter', 'when', 'Winter season', 1);
		if ($season->GetEndYear() > $season->GetStartYear())
		{
			$winter->SetChecked(true);
		}
		else
		{
			$summer->SetChecked(true);
		}
		$when = new XhtmlElement('div', $summer);
		$when->AddControl($winter);
		$when->SetCssClass('formControl');
		$when_legend = new XhtmlElement('legend', 'Time of year');
		$when_legend->SetCssClass('formLabel');
		$when_fs = new XhtmlElement('fieldset', $when_legend);
		$when_fs->SetCssClass('formPart');
		$when_fs->AddControl($when);
		$this->AddControl($when_fs);

		# add intro
		$intro_box = new TextBox('intro', $season->GetIntro(), $this->IsValidSubmit());
		$intro_box->SetMode(TextBoxMode::MultiLine());
		$intro = new FormPart('Introduction', $intro_box);
		$this->AddControl($intro);


		if (!$b_is_new_season)
		{
			# add types of matches
			if (!$this->IsPostback() or ($this->b_saving_new and $this->IsValidSubmit()))
			{
				foreach ($season->MatchTypes()->GetItems() as $i_type) $this->match_types_editor->DataObjects()->Add(new IdValue($i_type, ucfirst(MatchType::Text($i_type))));
			}
			$this->AddControl(new FormPart('Match types', $this->match_types_editor));

			# add results
			$result_container = new XhtmlElement('div');

			$result_box = new TextBox('results', $season->GetResults(), $this->IsValidSubmit());
			$result_box->SetMode(TextBoxMode::MultiLine());
			$result_container->AddControl($result_box);

			$result = new FormPart('Results', $result_container);
			$result->GetLabel()->AddAttribute('for', $result_box->GetXhtmlId());
			$this->AddControl($result);

			# Add rules table
			$rules = new XhtmlTable();
			$rules->SetCaption('Points for each result');
			$header = new XhtmlRow(array('Result', 'Points for home team', 'Points for away team'));
			$header->SetIsHeader(true);
			$rules->AddRow($header);

			foreach ($this->result_types as $result)
			{
				/* @var $result MatchResult */

				# Shouldn't ever need to assign points to a postponed match
				if ($result->GetResultType() == MatchResult::POSTPONED) continue;

				# Populate result with points from season rule
				$season->PossibleResults()->ResetCounter();
				while($season->PossibleResults()->MoveNext())
				{
					if ($season->PossibleResults()->GetItem()->GetResultType() == $result->GetResultType())
					{
						$result->SetHomePoints($season->PossibleResults()->GetItem()->GetHomePoints());
						$result->SetAwayPoints($season->PossibleResults()->GetItem()->GetAwayPoints());
						break;
					}
				}

				# Create table row
				$home_box = new TextBox($this->GetNamingPrefix() . 'Result' . $result->GetResultType() . 'Home', $result->GetHomePoints(), $this->IsValidSubmit());
				$away_box = new TextBox($this->GetNamingPrefix() . 'Result' . $result->GetResultType() . 'Away', $result->GetAwayPoints(), $this->IsValidSubmit());
				$home_box->AddAttribute('class', 'pointsBox');
				$away_box->AddAttribute('class', 'pointsBox');
				$result_row = new XhtmlRow(array(MatchResult::Text($result->GetResultType()), $home_box, $away_box));
				$rules->AddRow($result_row);
			}

			$result_container->AddControl($rules);

			# Add points adjustments
			$this->adjustments_editor->SetTeams($season->GetTeams());
			if (!$this->IsPostback() or ($this->b_saving_new and $this->IsValidSubmit())) $this->adjustments_editor->DataObjects()->SetItems($season->PointsAdjustments()->GetItems());
			$result_container->AddControl($this->adjustments_editor);

			# Show league table?
			$table = new CheckBox('showTable', 'Show results table', 1, $season->GetShowTable());
			$this->AddControl($table);

			$this->AddControl(new CheckBox('runsScored','Include runs scored', 1, $season->GetShowTableRunsScored()));
			$this->AddControl(new CheckBox('runsConceded', 'Include runs conceded', 1, $season->GetShowTableRunsConceded()));

			# add teams
			if (!$this->IsPostback() or ($this->b_saving_new and $this->IsValidSubmit()))
			{
				$teams_in_season = array();
				foreach ($season->GetTeams() as $team)
				{
					$teams_in_season[] = new TeamInSeason($team, $season, is_object($season->TeamsWithdrawnFromLeague()->GetItemByProperty('GetId', $team->GetId())));
				}
				$this->teams_editor->DataObjects()->SetItems($teams_in_season);
			}
			$this->AddControl(new FormPart('Teams', $this->teams_editor));
		}
	}


	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		# Validate fewer options for a new season than when editing, beacause a new season gets settings copied from last year to save time
		$b_is_new_season = !(bool)$this->GetDataObject()->GetId();

		if (!$this->IsInternalPostback())
		{
			require_once('data/validation/required-field-validator.class.php');
			require_once('data/validation/length-validator.class.php');
			require_once('data/validation/numeric-validator.class.php');

			$this->a_validators[] = new NumericValidator('competition', 'The competition identifier should be a number');
			$this->a_validators[] = new RequiredFieldValidator('start', 'Please add the year the season starts', ValidatorMode::AllFields());
			$this->a_validators[] = new NumericValidator('start', 'The start year must be a number');
			$this->a_validators[] = new LengthValidator('start', 'The start year should be in YYYY format', 4, 4);
			$this->a_validators[] = new LengthValidator('intro', 'Please make the introduction shorter', 0, 10000);
			if (!$b_is_new_season)
			{
				$this->a_validators[] = new LengthValidator('results', 'Please make the results shorter', 0, 10000);
				foreach($this->result_types as $o_result)
				{
					/* @var $o_result MatchResult */
					$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Result' . $o_result->GetResultType() . 'Home', 'The home points for ' . MatchResult::Text($o_result->GetResultType()) . ' must be a number. For example, \'2\', not \'two\'.');
					$this->a_validators[] = new NumericValidator($this->GetNamingPrefix() . 'Result' . $o_result->GetResultType() . 'Away', 'The away points for ' . MatchResult::Text($o_result->GetResultType()) . ' must be a number. For example, \'2\', not \'two\'.');
				}
			}
		}

		$a_aggregated_validators = array();
		if (!$b_is_new_season)
		{
			$a_aggregated_validators = $this->adjustments_editor->GetValidators();
			$a_aggregated_validators = array_merge($a_aggregated_validators, $this->match_types_editor->GetValidators());
			$a_aggregated_validators = array_merge($a_aggregated_validators, $this->teams_editor->GetValidators());
		}
		$this->a_validators = array_merge($this->a_validators, $a_aggregated_validators);
	}

}
?>
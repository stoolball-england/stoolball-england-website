<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('stoolball/match-type.enum.php');

/**
 * Editor for the seasons in which a stoolball tournament should be listed
 *
 */
class TournamentSeasonsControl extends DataEditControl
{
	/**
	 * The tournament of which the match is a part, if any
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Creates a TournamentSeasonsControl
	 *
	 * @param SiteSettings $o_settings
	 * @param Match $match
	 * @param bool $b_entire_form
	 */
	public function __construct(SiteSettings $o_settings, Match $match=null, $b_entire_form=true)
	{
		$this->SetDataObjectClass('Match');
		if (!is_null($match))
		{
			$match->SetMatchType(MatchType::TOURNAMENT);
			$this->SetDataObject($match);
		}
		parent::__construct($o_settings, $b_entire_form);

		$this->seasons = new Collection();
		$this->SetAllowCancel(true);

        # Change Save button to "Save tournament"
        $this->SetButtonText('Save tournament');
    }

	/**
	 * the season the match is in
	 *
	 * @var Collection
	 */
	private $seasons;

	/**
	 * Sets the the seasons the match is in
	 *
	 * @return Collection
	 */
	public function Seasons()
	{
		return $this->seasons;
	}


    private $show_step_number = false;
    
    /**
     * Sets whether to show 'Step x of x' in the panel header
     * @param bool $show
     */
    public function SetShowStepNumber($show) {
        $this->show_step_number = (bool)$show;
    }
    
    /**
     * Gets whether to show 'Step x of x' in the panel header
     */
    public function GetShowStepNumber() {
        return $this->show_step_number;
    }   
    
	/**
	 * Builds a match object containing the result information posted by the control
	 *
	 */
	public function BuildPostedDataObject()
	{
		$match = new Match($this->GetSettings());
		$match->SetMatchType(MatchType::TOURNAMENT);

		# Get match id
		$s_key = $this->GetNamingPrefix() . 'item';
		if (isset($_POST[$s_key]))
		{
			$s_id = $_POST[$s_key];
			if (strlen($s_id))
			{
				$match->SetId($s_id);
			}
		}

    	# Get the title
		$s_key = $this->GetNamingPrefix() . 'Title';
		if (isset($_POST[$s_key])) $match->SetTitle(strip_tags($_POST[$s_key]));

		$s_key = $this->GetNamingPrefix() . 'Seasons';
		if (isset($_POST[$s_key]) and strlen($_POST[$s_key]))
		{
			$possibly_posted = explode(';', $_POST[$s_key]);
			foreach ($possibly_posted as $season_id)
			{
				$s_key = $this->GetNamingPrefix() . 'Season' . $season_id;
				if (isset($_POST[$s_key]) and $_POST[$s_key] == $season_id)
				{
					$season = new Season($this->GetSettings());
					$season->SetId($_POST[$s_key]);
					$match->Seasons()->Add($season);
				}
			}
		}

		$this->SetDataObject($match);
	}

	protected function CreateControls()
	{
		$match = $this->GetDataObject();
		if (is_null($match))
		{
			$match = new Match($this->GetSettings());
			$match->SetMatchType(MatchType::TOURNAMENT);
		}

		/* @var $match Match */

		$match_box = new XhtmlElement('div');
        $this->CreateSeasonControls($match, $match_box);
	}
    
	/**
	 * Creates the controls when the editor is in its season view
	 *
	 */
	private function CreateSeasonControls(Match $match, XhtmlElement $match_box)
	{
		/* @var $season Season */
        $css_class = 'TournamentEdit checkBoxList';
        if ($this->GetCssClass()) $css_class .= ' ' . $this->GetCssClass();
        
        $match_outer_1 = new XhtmlElement('div');
        $match_outer_1->SetCssClass($css_class);
        $this->SetCssClass('');
        $match_outer_1->SetXhtmlId($this->GetNamingPrefix());

        $match_outer_2 = new XhtmlElement('div');
        $this->AddControl($match_outer_1);
        $match_outer_1->AddControl($match_outer_2);
        $match_outer_2->AddControl($match_box);

        $heading = 'Select seasons';
        if ($this->show_step_number) {
            $heading .= ' &#8211; step 3 of 3';
        }
        $o_title_inner_1 = new XhtmlElement('span', $heading);
        $o_title_inner_2 = new XhtmlElement('span', $o_title_inner_1);
        $o_title_inner_3 = new XhtmlElement('span', $o_title_inner_2);
        $match_box->AddControl(new XhtmlElement('h2', $o_title_inner_3, "large"));

		# Preserve match title, because we need it to send the email when the seasons are saved
		$title = new TextBox($this->GetNamingPrefix() . 'Title', $match->GetTitle(), $this->IsValidSubmit());
		$title->SetMode(TextBoxMode::Hidden());
		$match_box->AddControl($title);

		# If the list of seasons includes ones in which the only match type is tournament, then
		# they're annual tournaments like Expo and Seaford. Although those tournaments can be listed
		# in other seasons (they're of interest to the league teams), we don't want other tournaments
		# listed on pages which are supposed to be just about those annual tournaments. So exclude them
		# from the collection of possible seasons. Next bit of code will add them back in for any
		# tournaments which actually are meant to be in those seasons.

		$seasons = $this->seasons->GetItems();
		$len = count($seasons);
		for ($i = 0; $i < $len; $i++)
		{
			# Make sure only seasons which contain tournaments are listed. Necessary to include all match types
			# in the Seasons() collection from the database so that we can test what other match types seasons support.
			if (!$seasons[$i]->MatchTypes()->Contains(MatchType::TOURNAMENT))
			{
				unset($seasons[$i]);
				continue;
			}

			if ($seasons[$i]->MatchTypes()->GetCount() == 1 and $seasons[$i]->MatchTypes()->GetFirst() == MatchType::TOURNAMENT)
			{
				unset($seasons[$i]);
			}
		}
		$this->seasons->SetItems($seasons);


		# If the list of possible seasons doesn't include the one(s) the match is already in,
		# or the ones the context team plays in, add those to the list of possibles
		$a_season_ids = array();
		foreach ($this->seasons as $season) $a_season_ids[] = $season->GetId();
		foreach ($match->Seasons() as $season)
		{
			if (!in_array($season->GetId(), $a_season_ids, true))
			{
				$this->seasons->Insert($season);
				$a_season_ids[] = $season->GetId();
			}
		}

		if (isset($this->context_team))
		{
			$match_year = Date::Year($match->GetStartTime());
			foreach ($this->context_team->Seasons() as $team_in_season)
			{
				/* @var $team_in_season TeamInSeason */
				if (!in_array($team_in_season->GetSeasonId(), $a_season_ids, true) 
				    and ($team_in_season->GetSeason()->GetStartYear() == $match_year or $team_in_season->GetSeason()->GetEndYear() == $match_year))
				{
					$this->seasons->Insert($team_in_season->GetSeason());
					$a_season_ids[] = $team_in_season->GetSeasonId();
				}
			}
		}

		require_once('xhtml/forms/checkbox.class.php');
		$seasons_list = '';

		if ($this->seasons->GetCount())
		{
			# Sort the seasons by name, because they've been messed up by the code above
			$this->seasons->SortByProperty('GetCompetitionName');

			$match_box->AddControl(new XhtmlElement('p', 'Tick all the places we should list your tournament:'));
			$match_box->AddControl('<div class="radioButtonList">');

			foreach ($this->seasons as $season)
			{
				# Select season if it's one of the seasons the match is already in
				$b_season_selected = false;
				foreach ($match->Seasons() as $match_season)
				{
					$b_season_selected =  ($b_season_selected or $match_season->GetId() == $season->GetId());
				}

				/* @var $season Season */
				$box = new CheckBox($this->GetNamingPrefix() . 'Season' . $season->GetId(), $season->GetCompetitionName(), $season->GetId(), $b_season_selected, $this->IsValidSubmit());
				$seasons_list .= $season->GetId() . ';';
				$match_box->AddControl($box);
			}
			$match_box->AddControl('</div>');

			# Remember all the season ids to make it much easier to find the data on postback
			$seasons = new TextBox($this->GetNamingPrefix() . 'Seasons', $seasons_list, $this->IsValidSubmit());
			$seasons->SetMode(TextBoxMode::Hidden());
			$match_box->AddControl($seasons);
		}
		else
		{
			$match_month = 'in ' . Date::MonthAndYear($match->GetStartTime());
			$type = strtolower(PlayerType::Text($match->GetPlayerType()));
			$match_box->AddControl(new XhtmlElement('p', "Unfortunately we don't have details of any $type competitions $match_month to list your tournament in."));
			$match_box->AddControl(new XhtmlElement('p', 'Please click \'Save tournament\' to continue.'));
		}
	}
}
?>
<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('stoolball/match-type.enum.php');

/**
 * Editor for the teams attending a stoolball tournament
 *
 */
class TournamentTeamsControl extends DataEditControl
{
	/**
	 * The tournament of which the match is a part, if any
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Aggregated editor for teams
	 *
	 * @var TeamsInTournamentEditor
	 */
	private $teams_editor;
    
	/**
	 * Creates a TournamentTeamsControl
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

		$this->SetAllowCancel(true);
        $this->SetCssClass('TournamentEdit');

        # Change Save button to "Save tournament"
        $this->SetButtonText('Save tournament');
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
     * Lazy load of aggregated editors
     * @return void
     */
    private function EnsureAggregatedEditors()
    {
        if (!is_object($this->teams_editor))
        {
            require_once('stoolball/tournaments/teams-in-tournament-editor.class.php');
            $heading = 'Confirmed teams';
            if ($this->show_step_number) {
                $heading .= ' &#8211; step 2 of 3';
            }
            $this->teams_editor = new TeamsInTournamentEditor($this->GetSettings(), $this, 'AwayTeam', $heading, array('Team name'));
        }
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

        # Get the start date, because it's part of the title that has to be recreated for an internal postback
        $s_key = $this->GetNamingPrefix() . 'Start';
        if (isset($_POST[$s_key]))
        {
            $match->SetStartTime($_POST[$s_key]);
        }
        
        $s_key = $this->GetNamingPrefix() . "MaxTeams";
        if (isset($_POST[$s_key])) $match->SetMaximumTeamsInTournament($_POST[$s_key]);
        
		# Teams - get from aggregated editor
	    $this->EnsureAggregatedEditors();
		$a_teams = $this->teams_editor->DataObjects()->GetItems();
		foreach ($a_teams as $team) $match->AddAwayTeam($team);

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

        $this->CreateTeamControls($match, $match_box);
	}


    /**
     * Creates the controls when the editor is in its team view
     *
     */
    private function CreateTeamControls(Match $match, XhtmlElement $match_box)
    {
        $css_class = 'TournamentEdit';
        $match_box->SetCssClass($css_class);
        $this->SetCssClass('');
        $match_box->SetXhtmlId($this->GetNamingPrefix());
        $this->AddControl($match_box);
                
        # Preserve match title and date, because we need them for the page title when "add" is clicked
        $title = new TextBox($this->GetNamingPrefix() . 'Title', $match->GetTitle(), $this->IsValidSubmit());
        $title->SetMode(TextBoxMode::Hidden());
        $match_box->AddControl($title);

        $date = new TextBox($this->GetNamingPrefix() . 'Start', $match->GetStartTime(), $this->IsValidSubmit());
        $date->SetMode(TextBoxMode::Hidden());
        $match_box->AddControl($date);
        
        $match_box->AddControl(<<<HTML
        
        <div class="panel"><div><div>
            <h2 class="large"><span><span><span>How many teams do you have room for?</span></span></span></h2>
            <p>Tell us how many teams you have room for and who's coming, and we'll list your tournament with how many spaces are left.</p>
HTML
);
        
        
        $max = new TextBox($this->GetNamingPrefix() . 'MaxTeams', $match->GetMaximumTeamsInTournament(), $this->IsValidSubmit());
        $max->SetMode(TextBoxMode::Number());
        $max->SetCssClass('max-teams numeric');
        $match_box->AddControl(new FormPart("Maximum teams", new XhtmlElement('div', $max)));
        
        $match_box->AddControl("</div></div></div>");
        
        # Teams editor
        $this->EnsureAggregatedEditors();
        $this->teams_editor->DataObjects()->SetItems($match->GetAwayTeams());
        $match_box->AddControl($this->teams_editor);

        # Change Save button to "Next" button
        if ($this->show_step_number) {
            $this->SetButtonText('Next &raquo;');
        }
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
	    require_once 'data/validation/numeric-validator.class.php';
        require_once 'data/validation/numeric-range-validator.class.php';
		    		
		$this->a_validators = array();
        $this->AddValidator(new NumericValidator(array($this->GetNamingPrefix() . 'MaxTeams'), "'How many teams can play?' must be a number"));
        $this->AddValidator(new NumericRangeValidator(array($this->GetNamingPrefix() . 'MaxTeams'), "You need to allow more than two teams in a tournament", 3, 10000));
        
	    $this->EnsureAggregatedEditors();
		foreach ($this->teams_editor->GetValidators() as $validator) 
		{
		    $this->AddValidator($validator);
        }
    }
}
?>
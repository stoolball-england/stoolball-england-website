<?php
require_once('data/data-edit-control.class.php');
require_once('stoolball/match.class.php');
require_once('stoolball/match-type.enum.php');

/**
 * Editor for the matches in a stoolball tournament
 *
 */
class TournamentMatchesControl extends DataEditControl
{
	/**
	 * The tournament of which the match is a part, if any
	 *
	 * @var Match
	 */
	private $tournament;

	/**
	 * Aggregated editor for matches
	 *
	 * @var MatchesInTournamentEditor
	 */
	private $matches_editor;

	private $csrf_token;
    
	/**
	 * Creates a TournamentMatchesControl
	 *
	 * @param SiteSettings $o_settings
	 * @param string $csrf_token
	 * @param Match $match
	 * @param bool $b_entire_form
	 */
	public function __construct(SiteSettings $o_settings, $csrf_token, Match $match=null, $b_entire_form=true)
	{
		$this->SetDataObjectClass('Match');
		if (!is_null($match))
		{
			$match->SetMatchType(MatchType::TOURNAMENT);
			$this->SetDataObject($match);
		}
		parent::__construct($o_settings, $csrf_token, $b_entire_form);
		$this->csrf_token = $csrf_token;

		$this->SetAllowCancel(true);
        $this->SetCssClass('TournamentEdit');

        # Change Save button to "Save tournament"
        $this->SetButtonText('Save tournament');
    }

    /**
     * Lazy load of aggregated editors
     * @return void
     */
    private function EnsureAggregatedEditors()
    {
        if (!is_object($this->matches_editor))
        {
            require_once('stoolball/tournaments/matches-in-tournament-editor.class.php');
            $heading = 'Matches in this tournament';
            $this->matches_editor = new MatchesInTournamentEditor($this->GetSettings(), $this, 'Matches', $heading, $this->csrf_token);
        }
    }
    
	/**
	 * Builds a match object containing the result information posted by the control
	 *
	 */
	public function BuildPostedDataObject()
	{
		$tournament = new Match($this->GetSettings());
		$tournament->SetMatchType(MatchType::TOURNAMENT);

		# Get match id
		$s_key = $this->GetNamingPrefix() . 'item';
		if (isset($_POST[$s_key]))
		{
			$s_id = $_POST[$s_key];
			if (strlen($s_id))
			{
				$tournament->SetId($s_id);
			}
		}

    	# Get the title
		$s_key = $this->GetNamingPrefix() . 'Title';
		if (isset($_POST[$s_key])) $tournament->SetTitle(strip_tags($_POST[$s_key]));

        # Get the start date, because it's part of the title that has to be recreated for an internal postback
        $s_key = $this->GetNamingPrefix() . 'Start';
        if (isset($_POST[$s_key]))
        {
            $tournament->SetStartTime($_POST[$s_key]);
        }
        
		# Matches - get from aggregated editor
	    $this->EnsureAggregatedEditors();
		$matches = $this->matches_editor->DataObjects()->GetItems();
		foreach ($matches as $match) {
		    $tournament->AddMatchInTournament($match);
        }

		$this->SetDataObject($tournament);
	}

	protected function CreateControls()
	{
		$tournament = $this->GetDataObject();
		if (is_null($tournament))
		{
			$tournament = new Match($this->GetSettings());
			$tournament->SetMatchType(MatchType::TOURNAMENT);
		}

		/* @var $match Match */

		$match_box = new XhtmlElement('div');

        $this->CreateMatchControls($tournament, $match_box);
	}


    /**
     * Creates the controls to edit the matches
     *
     */
    private function CreateMatchControls(Match $tournament, XhtmlElement $box)
    {
        $css_class = 'TournamentEdit';
        $box->SetCssClass($css_class);
        $this->SetCssClass('');
        $box->SetXhtmlId($this->GetNamingPrefix());
        $this->AddControl($box);
                
        # Preserve tournament title and date, because we need them for the page title when "add" is clicked
        $title = new TextBox($this->GetNamingPrefix() . 'Title', $tournament->GetTitle(), $this->IsValidSubmit());
        $title->SetMode(TextBoxMode::Hidden());
        $box->AddControl($title);

        $date = new TextBox($this->GetNamingPrefix() . 'Start', $tournament->GetStartTime(), $this->IsValidSubmit());
        $date->SetMode(TextBoxMode::Hidden());
        $box->AddControl($date);
        
        # Matches editor
        $this->EnsureAggregatedEditors();
        $this->matches_editor->DataObjects()->SetItems($tournament->GetMatchesInTournament());
        $this->matches_editor->SetTeams($tournament->GetAwayTeams());
        $box->AddControl($this->matches_editor);
    }

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		$this->a_validators = array();
	    $this->EnsureAggregatedEditors();
		foreach ($this->matches_editor->GetValidators() as $validator) 
		{
		    $this->AddValidator($validator);
        }
    }
}
?>
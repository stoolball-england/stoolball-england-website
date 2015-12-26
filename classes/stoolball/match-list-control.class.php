<?php
require_once('xhtml/xhtml-element.class.php');
require_once('stoolball/match.class.php');
require_once('data/date.class.php');

/**
 * A list of stoolball matches, by default linked and marked up as hCalendar events
 *
 */
class MatchListControl extends XhtmlElement
{
	private $matches;
	private $b_microformats = true;
	private $label_types = array();

	public function __construct($matches=null)
	{
		parent::XhtmlElement('ul');
		$this->matches = (is_array($matches)) ? $matches : array();
	}

	/**
	 * Sets the match types which will be explictly stated
	 *
	 * @param MatchType[] $types
	 */
	public function SetMatchTypesToLabel($types)
	{
		if (is_array($types)) $this->label_types = $types;
	}

	/**
	 * Gets the match types which will be explictly stated
	 *
	 * @return MatchType[]
	 */
	public function GetMatchTypesToLabel()
	{
		return $this->label_types;
	}

	/**
	 * Gets or sets whether to mark up the control using microformats
	 *
	 * @param bool $b_use
	 * @return bool
	 */
	public function UseMicroformats($b_use=null)
	{
		if (!is_null($b_use)) $this->b_microformats = (bool)$b_use;
		return $this->b_microformats;
	}

	protected function OnPreRender()
	{
		/* @var $match Match */

		$b_label_types = count($this->label_types);
        $i_next_match_day = 0;

		if ($this->b_microformats) $this->AddCssClass('vcalendar'); # hCalendar

		foreach ($this->matches as $match)
		{
		    # Create list item for match
			$li = new XhtmlElement('li');           
            $i_next_match_day = $this->AddDateClasses($match, $i_next_match_day, $li);
            $this->AddControl($li);
            
            # Add link to match
            $link = $this->CreateLinkToMatch($match);
			$li->AddControl($link);

			# add match type if not default for season
			if ($b_label_types and in_array($match->GetMatchType(), $this->label_types, true) and strpos($match->GetTitle(), MatchType::Text($match->GetMatchType())) === false)
			{
				$li->AddControl(' (' . htmlentities(MatchType::Text($match->GetMatchType()), ENT_QUOTES, "UTF-8", false) . ')');
			}

            # For tournaments, add extra detail
            if ($match->GetMatchType() === MatchType::TOURNAMENT)
            { 
                $tournament = new XhtmlElement("p", null, "tournament-detail");
                $li->AddControl($tournament);
            }
            
            # Add match date
            if ($match->GetStartTime())
			{        
		        $date = $this->CreateStartDate($match);
            	switch ($match->GetMatchType())
                {
                    case MatchType::TOURNAMENT_MATCH:
                        $date->AddCssClass('metadata');
                        $li->AddControl($date);
                        break;
                    case MatchType::TOURNAMENT:
                        $tournament->AddControl($date);
                        break;
                    default:
                        $li->AddControl(' &#8211; ');
                        $li->AddControl($date);
                        break;
				}
			}

            # More tournament info
            if ($match->GetMatchType() === MatchType::TOURNAMENT)
            {
                $fullstop = false;
                if ($match->GetQualificationType() === MatchQualification::OPEN_TOURNAMENT)
                {
                    $tournament->AddControl(". ");
                    $fullstop = true;
                    $tournament->AddControl("Open. ");
                }  
                else if ($match->GetQualificationType() === MatchQualification::CLOSED_TOURNAMENT)
                {
                    $tournament->AddControl(". ");
                    $fullstop = true;
                    $tournament->AddControl("Invited teams only. ");
                }
                
                if ($match->GetSpacesLeftInTournament() and $match->GetQualificationType() !== MatchQualification::CLOSED_TOURNAMENT) {
                    if (!$fullstop) {
                        $tournament->AddControl(". ");
                        $fullstop = true;
                    }
                    $tournament->AddControl('<strong class="spaces-left">' . $match->GetSpacesLeftInTournament() . " spaces.</strong> ");
                }
            }
            
            # Add meta data
			if ($this->b_microformats)
			{
                $this->AddMetadata($match, $li);
			}
		}
	}

    private function CreateLinkToMatch(Match $match) 
    {
        $text = $match->GetTitle();
        
        # Add player type for tournaments
        if ($match->GetPlayerType()
            and $match->GetMatchType() === MatchType::TOURNAMENT 
            and strpos(strtolower($match->GetTitle()), strtolower(PlayerType::Text($match->GetPlayerType()))) === false)
        {
            $text .= ' (' . htmlentities(PlayerType::Text($match->GetPlayerType()), ENT_QUOTES, "UTF-8", false) . ')';
        }

        $link = new XhtmlAnchor();
        $link->AddControl(htmlentities($text, ENT_QUOTES, "UTF-8", false));
        $link->SetNavigateUrl($match->GetNavigateUrl());

        if ($this->b_microformats)
        {
            $link->SetCssClass('summary url'); # hCalendar
            $link->AddAttribute("rel", "schema:url");
            $link->AddAttribute("property", "schema:name");
            $title_meta = new XhtmlElement('span', ' (stoolball)');
            $title_meta->SetCssClass('metadata');
            $link->AddControl($title_meta);
        }
        return $link;
    }
    
    private function CreateStartDate(Match $match) 
    {
        $date = new XhtmlElement('abbr', htmlentities($match->GetStartTimeFormatted(), ENT_QUOTES, "UTF-8", false));
        $date->SetTitle(Date::Microformat($match->GetStartTime())); # hCalendar
        $date->SetCssClass('dtstart'); # hCalendar
        $date->AddAttribute("property", "schema:startDate");
        $date->AddAttribute("datatype", "xsd:date");
        $date->AddAttribute("content", Date::Microformat($match->GetStartTime()));
        return $date;
    }
    
    private function CreateEndDate(Match $match) 
    {
        $i_end_time = $match->GetStartTime() + (60*90);
        if ($match->GetMatchType() == MatchType::TOURNAMENT_MATCH) $i_end_time = $match->GetStartTime() + (60*45); # 45 mins
        if ($match->GetMatchType() == MatchType::TOURNAMENT) $i_end_time = $match->GetStartTime() + (60*420); # 7 hours
        $hcal_end = new XhtmlElement('abbr', ' until around ' . htmlentities(Date::Time($i_end_time), ENT_QUOTES, "UTF-8", false));
        $hcal_end->SetTitle(Date::Microformat($i_end_time));
        $hcal_end->SetCssClass('dtend');
        return $hcal_end;
    }
    
    private function AddDateClasses(Match $match, $i_next_match_day, XhtmlElement $li) 
    {
        if ($match->GetStartTime())
        {
            $i_now = gmdate('U');
            $i_two_hours_ago = gmmktime(gmdate('H', $i_now), gmdate('i', $i_now), 0, gmdate('m', $i_now), gmdate('d', $i_now), gmdate('Y', $i_now))-7200;
            $i_match_time = $match->GetStartTime();
            $i_match_day = gmmktime(12, 0, 0, gmdate('m', $i_match_time), gmdate('d', $i_match_time), gmdate('Y', $i_match_time));
    
            if ($i_match_time <= $i_two_hours_ago)
            {
                $li->AddCssClass('matchPlayed');
            }
            else
            {
                if (!$i_next_match_day) $i_next_match_day = $i_match_day;
    
                if ($i_match_day == $i_next_match_day && !$match->Result()->GetResultType())
                {
                    $li->AddCssClass('matchNext');
                }
            }
        }        
        return $i_next_match_day;
    }

    private function AddMetadata(Match $match, XhtmlElement $li) 
    {
        $li->AddCssClass('vevent'); # hCalendar
        $li->AddAttribute("typeof", "schema:SportsEvent");
        $li->AddAttribute("about", $match->GetLinkedDataUri());
        
        $meta = new XhtmlElement('span');
        $meta->SetCssClass('metadata');
        
        # hCalendar end date
        if ($match->GetStartTime() and $match->GetIsStartTimeKnown())
        {
            $end_date = $this->CreateEndDate($match);
            $meta->AddControl($end_date);
        }

        # hCalendar location
        if (!is_null($match->GetGround()))
        {
            $ground = new XhtmlElement('span', htmlentities($match->GetGround()->GetNameAndTown(), ENT_QUOTES, "UTF-8", false));
            $ground->SetCssClass('location');
            $meta->AddControl(' at ');
            $meta->AddControl($ground);
        }

        # hCalendar description
        $hcal_desc = new XhtmlElement('span');
        $hcal_desc->SetCssClass('description');
        $i_seasons = $match->Seasons()->GetCount();
        if ($i_seasons)
        {
            $meta->AddControl(' in ');
            $seasons = $match->Seasons()->GetItems();
            for ($i = 0; $i < $i_seasons; $i++)
            {
                $b_last = ($i > 0 and $i == ($i_seasons-1));
                if ($i > 0 and !$b_last)
                {
                    $hcal_desc->AddControl(', ');
                }
                elseif ($b_last)
                {
                    $hcal_desc->AddControl(' and ');
                }
                $hcal_desc->AddControl(htmlentities($seasons[$i]->GetCompetitionName(), ENT_QUOTES, "UTF-8", false));
            }
        }
        if (!$match->GetIsStartTimeKnown()) $hcal_desc->AddControl('. Start time not known');
        if ($hcal_desc->CountControls()) $meta->AddControl($hcal_desc);

        # hCalendar timestamp
        $meta->AddControl('. At ');
        $hcal_stamp = new XhtmlElement('abbr', htmlentities(Date::Time(gmdate('U')), ENT_QUOTES, "UTF-8", false));
        $hcal_stamp->SetTitle(Date::Microformat());
        $hcal_stamp->SetCssClass('dtstamp');
        $meta->AddControl($hcal_stamp);

        # hCalendar GUID
        $meta->AddControl(' match ');
        $hcal_guid = new XhtmlElement('span', htmlentities($match->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false));
        $hcal_guid->SetCssClass('uid');
        $meta->AddControl($hcal_guid);

        # hCalendar status
        $s_status = 'CONFIRMED';
        switch ($match->Result()->GetResultType())
        {
            case MatchResult::CANCELLED:
            case MatchResult::POSTPONED:
            case MatchResult::AWAY_WIN_BY_FORFEIT:
            case MatchResult::HOME_WIN_BY_FORFEIT:
                $s_status = 'CANCELLED';
        }

        $meta->AddControl(' is ');
        $status = new XhtmlElement('span', $s_status);
        $status->SetCssClass('status');
        $meta->AddControl($status);
        $meta->AddControl('.');
        
        $li->AddControl($meta);
    }
}
?>
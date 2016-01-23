<?php
require_once ('xhtml/placeholder.class.php');
require_once ('data/date.class.php');
require_once ('stoolball/match-list-control.class.php');
require_once ('stoolball/team-list-control.class.php');
require_once 'stoolball/player-type.enum.php';

class TournamentControl extends Placeholder
{
	/**
	 * The tournament
	 *
	 * @var Match
	 */
	private $match;

	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $settings;

	function __construct(SiteSettings $settings, Match $match)
	{
		if ($match->GetMatchType() != MatchType::TOURNAMENT)
			throw new Exception('No match for tournament control');

		$this->settings = $settings;
		$this->match = $match;
	}

	function OnPreRender()
	{
		/* @var $match Match */
		/* @var $o_home Team */
		/* @var $o_away Team */

		$match = $this->match;
        $player_type = PlayerType::Text($match->GetPlayerType());

		# Show time and place
		$date = new XhtmlElement('p', 'When: ' . $match->GetStartTimeFormatted());
		$date->AddAttribute("property", "schema:startDate");
		$date->AddAttribute("datatype", "xsd:date");
		$date->AddAttribute("content", Date::Microformat($match->GetStartTime()));
		$this->AddControl($date);

		# If we know the time and place, show when the sun sets
		# TODO: assumes UK
		if ($match->GetGround() instanceof Ground and $match->GetGround()->GetAddress()->GetLatitude() and $match->GetGround()->GetAddress()->GetLongitude())
		{
			$date->AddControl(' <span class="sunset">sunset ' . Date::Time(date_sunset($match->GetStartTime(), SUNFUNCS_RET_TIMESTAMP, $match->GetGround()->GetAddress()->GetLatitude(), $match->GetGround()->GetAddress()->GetLongitude())) . '</span>');
		}

		$ground = $match->GetGround();
		if (is_object($ground))
		{
			$ground_link = new XhtmlElement('a', $ground->GetNameAndTown());
			$ground_link->AddAttribute('href', $ground->GetNavigateUrl());
			$ground_link->SetCssClass('location');
			# hCalendar
			$ground_link->AddAttribute("typeof", "schema:Place");
			$ground_link->AddAttribute("about", $ground->GetLinkedDataUri());
			$ground_link->AddAttribute("rel", "schema:url");
			$ground_link->AddAttribute("property", "schema:name");
			$ground_control = new XhtmlElement('p', 'Where: ');
			$ground_control->AddAttribute("rel", "schema:location");
			$ground_control->AddControl($ground_link);
			$this->AddControl($ground_control);
		}

		# Format
		if ($match->GetIsOversKnown())
		{
			$this->AddControl(new XhtmlElement("p", "Matches are " . $match->GetOvers() . " overs."));
		}

		# Add teams
		$a_teams = $match->GetAwayTeams();
		if (!is_array($a_teams))
			$a_teams = array();
		if (!is_null($match->GetHomeTeam()))
			array_unshift($a_teams, $match->GetHomeTeam());
		# shouldn't be home teams any more, but doesn't hurt!
		$how_many_teams = count($a_teams);

		$qualification = $match->GetQualificationType();
		if ($qualification === MatchQualification::OPEN_TOURNAMENT)
		{
			$qualification = "Any " . strtolower($player_type) . " team may enter this tournament. ";
		}
		else if ($qualification === MatchQualification::CLOSED_TOURNAMENT)
		{
			$qualification = "Only invited or qualifying teams may enter this tournament. ";
		}
		else
		{
			$qualification = "";
		}

        $show_spaces_left = ($match->GetMaximumTeamsInTournament() and gmdate('U') < $match->GetStartTime() and $match->GetQualificationType() !== MatchQualification::CLOSED_TOURNAMENT);
		if ($match->GetIsMaximumPlayersPerTeamKnown() or $show_spaces_left or $how_many_teams or $qualification)
		{
			$this->AddControl(new XhtmlElement('h2', 'Teams'));
		}

		$who_can_play = "";
		if ($match->GetIsMaximumPlayersPerTeamKnown())
		{
			$who_can_play = Html::Encode($match->GetMaximumPlayersPerTeam() . " players per team. ");
		}
        
		$who_can_play .= Html::Encode($qualification);
		
        if ($show_spaces_left) {
            $who_can_play .= '<strong class="spaces-left">' . $match->GetSpacesLeftInTournament() . " spaces left.</strong>";
        }
        
		if ($who_can_play)
		{
			$this->AddControl("<p>" . trim($who_can_play) . "</p>");
		}

		if ($how_many_teams)
		{
			$teams = new TeamListControl($a_teams);
			$this->AddControl('<div rel="schema:performers">' . $teams . '</div>');
		}

		# Add matches
		$a_matches = $match->GetMatchesInTournament();
		if (is_array($a_matches) && count($a_matches))
		{
			$o_hmatches = new XhtmlElement('h2', 'Matches');
			$matches = new MatchListControl($a_matches); ;
			$matches->AddAttribute("rel", "schema:subEvents");

			$this->AddControl($o_hmatches);
			$this->AddControl($matches);
		}

		# Add notes

		if ($match->GetNotes() or $match->Seasons()->GetCount() > 0)
		{
			$this->AddControl(new XhtmlElement('h2', 'Notes'));
		}

		if ($match->GetNotes())
		{
			$s_notes = XhtmlMarkup::ApplyCharacterEntities($match->GetNotes());
			
            require_once('email/email-address-protector.class.php');
            $protector = new EmailAddressProtector($this->settings);
            $s_notes = $protector->ApplyEmailProtection($s_notes, AuthenticationManager::GetUser()->IsSignedIn());
			
			$s_notes = XhtmlMarkup::ApplyHeadings($s_notes);
			$s_notes = XhtmlMarkup::ApplyParagraphs($s_notes);
			$s_notes = XhtmlMarkup::ApplyLists($s_notes);
			$s_notes = XhtmlMarkup::ApplySimpleTags($s_notes);
			$s_notes = XhtmlMarkup::ApplyLinks($s_notes);
			if (strpos($s_notes, '<p>') > -1)
			{
				$this->AddControl($s_notes);
			}
			else
			{
				$this->AddControl(new XhtmlElement('p', $s_notes));
			}
		}

		# Show details of the seasons
		if ($match->Seasons()->GetCount() == 1)
		{
			$season = $match->Seasons()->GetFirst();
			$season_name = new XhtmlAnchor($season->GetCompetitionName(), $season->GetNavigateUrl());
			$b_the = !(stristr($season->GetCompetitionName(), 'the ') === 0);

			$this->AddControl(new XhtmlElement('p', 'This tournament is listed in ' . ($b_the ? 'the ' : '') . $season_name->__toString() . '.'));
		}
		elseif ($match->Seasons()->GetCount() > 1)
		{
			$this->AddControl(new XhtmlElement('p', 'This tournament is listed in the following seasons: '));

			$season_list = new XhtmlElement('ul');
			$this->AddControl($season_list);
			$seasons = $match->Seasons()->GetItems();
			$total_seasons = count($seasons);
			for ($i = 0; $i < $total_seasons; $i++)
			{
				$season = $seasons[$i];
				/* @var $season Season */
				$season_name = new XhtmlAnchor($season->GetCompetitionName(), $season->GetNavigateUrl());
				$li = new XhtmlElement('li', $season_name);
				if ($i < $total_seasons - 2)
				{
					$li->AddControl(new XhtmlElement('span', ', ', 'metadata'));
				}
				else if ($i < $total_seasons - 1)
				{
					$li->AddControl(new XhtmlElement('span', ' and ', 'metadata'));
				}
				$season_list->AddControl($li);
			}
		}

	}

}
?>
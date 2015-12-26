<?php
require_once('xhtml/xhtml-element.class.php');
require_once('data/date.class.php');
require_once('stoolball/match.class.php');

class MatchControl extends XhtmlElement
{
	/**
	 * The match to display
	 *
	 * @var Match
	 */
	private $o_match;

	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $o_settings;

	public function __construct(SiteSettings $o_settings, Match $o_match)
	{
		parent::XhtmlElement('div');
		$this->SetCssClass('match vevent');
		$this->AddAttribute("typeof", "schema:SportsEvent");
		$this->AddAttribute("about", $o_match->GetLinkedDataUri());
		$this->o_settings = $o_settings;
		$this->o_match = $o_match;
	}

	protected function OnPreRender()
	{
		/* @var $o_home Team */
		/* @var $o_away Team */
		/* @var $o_tourney Match */

		$o_title = new XhtmlElement('h1', htmlentities($this->o_match->GetTitle(), ENT_QUOTES, "UTF-8", false));
		$o_title->AddAttribute("property", "schema:name");
		$this->AddControl($o_title);

		# hCalendar
		$o_title->SetCssClass('summary');
		$o_title_meta = new XhtmlElement('span', ' (stoolball)');
		$o_title_meta->SetCssClass('metadata');
		$o_title->AddControl($o_title_meta);

		# Date and tournament
		
		$o_date_para = new XhtmlElement('p');
		$o_date = new XhtmlElement('abbr', htmlentities($this->o_match->GetStartTimeFormatted(), ENT_QUOTES, "UTF-8", false));
		$o_date->SetTitle(Date::Microformat($this->o_match->GetStartTime())); # hCalendar
		$o_date->SetCssClass('dtstart'); # hCalendar
		$o_date->AddAttribute("property", "schema:startDate");
		$o_date->AddAttribute("datatype", "xsd:date");
		$o_date->AddAttribute("content", Date::Microformat($this->o_match->GetStartTime()));
		$o_date_para->AddControl('When: ');
		$o_date_para->AddControl($o_date);

		# hCalendar end date
		if ($this->o_match->GetIsStartTimeKnown())
		{
			$i_end_time = $this->o_match->GetStartTime() + (60*90);
			$o_hcal_end = new XhtmlElement('abbr', ' until around ' . htmlentities(Date::Time($i_end_time), ENT_QUOTES, "UTF-8", false));
			$o_hcal_end->SetTitle(Date::Microformat($i_end_time));
			$o_hcal_end->SetCssClass('metadata dtend');
			$o_date_para->AddControl($o_hcal_end);
		}

		# If we know the time and place, show when the sun sets
		# TODO: Assumes UK
		if ($this->o_match->GetGround() instanceof Ground and $this->o_match->GetGround()->GetAddress()->GetLatitude() and $this->o_match->GetGround()->GetAddress()->GetLongitude())
		{
			$o_date_para->AddControl(' <span class="sunset">sunset ' . htmlentities(Date::Time(date_sunset($this->o_match->GetStartTime(), SUNFUNCS_RET_TIMESTAMP, $this->o_match->GetGround()->GetAddress()->GetLatitude(), $this->o_match->GetGround()->GetAddress()->GetLongitude())), ENT_QUOTES, "UTF-8", false) . '</span>');
		}


		# Display match type/season/tournament
		if ($this->o_match->GetMatchType() == MatchType::TOURNAMENT_MATCH)
		{
			$o_date_para->SetCssClass('description'); # hCal
			$o_tourney = $this->o_match->GetTournament();
			if (is_object($o_tourney))
			{
				$tournament_link = new XhtmlAnchor(htmlentities($o_tourney->GetTitle(), ENT_QUOTES, "UTF-8", false), $o_tourney->GetNavigateUrl());
				$tournament_link->AddAttribute("typeof", "schema:SportsEvent");
				$tournament_link->AddAttribute("about", $o_tourney->GetLinkedDataUri());
				$tournament_link->AddAttribute("rel", "schema:url");
				$tournament_link->AddAttribute("property", "schema:name");

				$tournament_container = new XhtmlElement("span", $tournament_link);
				$tournament_container->AddAttribute("rel", "schema:superEvent");

				# Check for 'the' to get the grammar right
				$s_title = strtolower($o_tourney->GetTitle());
				if (strlen($s_title) >= 4 and substr($s_title, 0, 4) == 'the ')
				{
					$o_date_para->AddControl(', in ');
				}
				else
				{
					$o_date_para->AddControl(', in the ');
				}
				$o_date_para->AddControl($tournament_container);
				$o_date_para->AddControl('.');
			}
			else
			{
				$o_date_para->AddControl(', in a tournament.');
			}
		}
		else
		{
			# hCalendar desc, built up at the same time as the date and league/tournament
			$hcal_desc = new XhtmlElement('div', null, 'description');
			$this->AddControl($hcal_desc);

			$s_detail_xhtml = ucfirst(MatchType::Text($this->o_match->GetMatchType()));
			$season_list_xhtml = null;

			if ($this->o_match->Seasons()->GetCount() == 1)
			{
				$season = $this->o_match->Seasons()->GetFirst();
				$season_name = new XhtmlAnchor(htmlentities($season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false), $season->GetNavigateUrl());
				$b_the = !(stristr($season->GetCompetitionName(), 'the ') === 0);
				$s_detail_xhtml .= ' in ' . ($b_the ? 'the ' : '') . $season_name->__toString() . '.';
			}
			elseif ($this->o_match->Seasons()->GetCount() > 1)
			{
				$s_detail_xhtml .= ' in the following seasons: ';

				$season_list_xhtml = new XhtmlElement('ul');
				$seasons = $this->o_match->Seasons()->GetItems();
				$total_seasons = count($seasons);
				for ($i = 0; $i < $total_seasons; $i++)
				{
					$season = $seasons[$i];
					/* @var $season Season */
					$season_name = new XhtmlAnchor(htmlentities($season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false), $season->GetNavigateUrl());
					$li = new XhtmlElement('li', $season_name);
					if ($i < $total_seasons-2)
					{
						$li->AddControl(new XhtmlElement('span', ', ', 'metadata'));
					}
					else if ($i < $total_seasons-1)
					{
						$li->AddControl(new XhtmlElement('span', ' and ', 'metadata'));
					}
					$season_list_xhtml->AddControl($li);
				}
			}
			else
			{
				$s_detail_xhtml .= '.';
			}

			$hcal_desc->AddControl(new XhtmlElement('p',  $s_detail_xhtml));
			if (!is_null($season_list_xhtml)) $hcal_desc->AddControl($season_list_xhtml);
		}

		$this->AddControl($o_date_para);

		# Ground
		$o_ground = $this->o_match->GetGround();
		if (is_object($o_ground))
		{
			$o_ground_link = new XhtmlElement('a', htmlentities($o_ground->GetNameAndTown(), ENT_QUOTES, "UTF-8", false));
			$o_ground_link->AddAttribute('href', $o_ground->GetNavigateUrl());
			$o_ground_link->SetCssClass('location'); # hCalendar
			$o_ground_link->AddAttribute("typeof", "schema:Place");
			$o_ground_link->AddAttribute("about", $o_ground->GetLinkedDataUri());
			$o_ground_link->AddAttribute("rel", "schema:url");
			$o_ground_link->AddAttribute("property", "schema:name");
			$o_ground_control = new XhtmlElement('p', 'Where: ');
			$o_ground_control->AddAttribute("rel", "schema:location");
			$o_ground_control->AddControl($o_ground_link);
			$this->AddControl($o_ground_control);
		}

		# Add result
		$o_result = $this->o_match->Result();
		$o_home = $this->o_match->GetHomeTeam();
		$o_away = $this->o_match->GetAwayTeam();
		$b_result_known = (!$o_result->GetResultType() == MatchResult::UNKNOWN);
		$b_batting_order_known = !is_null($this->o_match->Result()->GetHomeBattedFirst());

		$has_scorecard_data = ($o_result->HomeBatting()->GetCount() or
		$o_result->HomeBowling()->GetCount() or
		$o_result->AwayBatting()->GetCount() or
		$o_result->AwayBowling()->GetCount() or
		$o_result->GetHomeRuns() or
		$o_result->GetHomeWickets() or
		$o_result->GetAwayRuns() or
		$o_result->GetAwayWickets());

		$has_player_of_match = $this->o_match->Result()->GetPlayerOfTheMatch() instanceof Player and $this->o_match->Result()->GetPlayerOfTheMatch()->GetName();
		$has_player_of_match_home = $this->o_match->Result()->GetPlayerOfTheMatchHome() instanceof Player and $this->o_match->Result()->GetPlayerOfTheMatchHome()->GetName();
		$has_player_of_match_away = $this->o_match->Result()->GetPlayerOfTheMatchAway() instanceof Player and $this->o_match->Result()->GetPlayerOfTheMatchAway()->GetName();

		if ($b_result_known or $b_batting_order_known or $has_scorecard_data)
		{
			# Put result in header, so long as we have something to put after it. Otherwise put the result after it.
			$result_header = "Result";
			if ($b_result_known and ($b_batting_order_known or $has_scorecard_data)) $result_header .= ": " . $this->o_match->GetResultDescription();

			$result_header = new XhtmlElement('h2', htmlentities($result_header, ENT_QUOTES, "UTF-8", false));
			if ($has_scorecard_data) $result_header->AddCssClass("hasScorecard");
			$this->AddControl($result_header);
		}

		# If at least one player recorded, create a container for the schema.org metadata
		if ($has_scorecard_data or $has_player_of_match or $has_player_of_match_home or $has_player_of_match_away)
		{
			$this->AddControl('<div rel="schema:performers">');
		}

		if ($has_scorecard_data)
		{
			$this->CreateScorecard($this->o_match);
		}
		else
		{
			# Got to be just result and batting order now. Only include result if batting order's not there, otherwise result will already be in header.
			if ($b_result_known and !$b_batting_order_known)
			{
				$this->AddControl(new XhtmlElement('p', htmlentities($this->o_match->GetResultDescription(), ENT_QUOTES, "UTF-8", false) . '.'));
			}

			if ($b_batting_order_known)
			{
				$this->AddControl(new XhtmlElement('p', htmlentities(($this->o_match->Result()->GetHomeBattedFirst() ? $o_home->GetName() : $o_away->GetName()) . ' batted first.'), ENT_QUOTES, "UTF-8", false));
			}
		}

		# Player of the match
		if ($has_player_of_match)
		{
			$player = $this->o_match->Result()->GetPlayerOfTheMatch();
			$team = ($player->Team()->GetId() == $o_home->GetId()) ? $o_home->GetName() : $o_away->GetName();
			$player_of_match = new XhtmlElement('p', 'Player of the match: <a property="schema:name" rel="schema:url" href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a> ($team)");
			$player_of_match->AddAttribute("typeof", "schema:Person");
			$player_of_match->AddAttribute("about", $player->GetLinkedDataUri());
			$this->AddControl($player_of_match);
		}

		if ($has_player_of_match_home)
		{
			$player = $this->o_match->Result()->GetPlayerOfTheMatchHome();
			$player_of_match = new XhtmlElement('p', $o_home->GetName() . ' player of the match: <a property="schema:name" rel="schema:url" href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a>");
			$player_of_match->AddAttribute("typeof", "schema:Person");
			$player_of_match->AddAttribute("about", $player->GetLinkedDataUri());
			$this->AddControl($player_of_match);
		}
		if ($has_player_of_match_away)
		{
			$player = $this->o_match->Result()->GetPlayerOfTheMatchAway();
			$player_of_match = new XhtmlElement('p', $o_away->GetName() . ' player of the match: <a property="schema:name" rel="schema:url" href="' . htmlentities($player->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($player->GetName(), ENT_QUOTES, "UTF-8", false) . "</a>");
			$player_of_match->AddAttribute("typeof", "schema:Person");
			$player_of_match->AddAttribute("about", $player->GetLinkedDataUri());
			$this->AddControl($player_of_match);
		}

		# End container for the schema.org metadata
		if ($has_scorecard_data or $has_player_of_match or $has_player_of_match_home or $has_player_of_match_away)
		{
			$this->AddControl('</div>');
		}

		# Add notes
		if ($this->o_match->GetNotes())
		{
			$this->AddControl(new XhtmlElement('h2', 'Notes'));

            $s_notes = htmlentities($this->o_match->GetNotes(), ENT_QUOTES, "UTF-8", false);
			$s_notes = XhtmlMarkup::ApplyCharacterEntities($s_notes);

            require_once('email/email-address-protector.class.php');
            $protector = new EmailAddressProtector($this->o_settings);
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

		# hCalendar metadata
		$o_hcal_para = new XhtmlElement('p');
		$o_hcal_para->SetCssClass('metadata');
		$this->AddControl($o_hcal_para);

		# hCalendar timestamp
		$o_hcal_para->AddControl('Status: At ');
		$o_hcal_stamp = new XhtmlElement('abbr', htmlentities(Date::Time(gmdate('U')), ENT_QUOTES, "UTF-8", false));
		$o_hcal_stamp->SetTitle(Date::Microformat());
		$o_hcal_stamp->SetCssClass('dtstamp');
		$o_hcal_para->AddControl($o_hcal_stamp);

		# hCalendar GUID
		$o_hcal_para->AddControl(' match ');
		$o_hcal_guid = new XhtmlElement('span', htmlentities($this->o_match->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false));
		$o_hcal_guid->SetCssClass('uid');
		$o_hcal_para->AddControl($o_hcal_guid);

		# work out hCalendar status
		$s_status = 'CONFIRMED';
		switch ($this->o_match->Result()->GetResultType())
		{
			case MatchResult::CANCELLED:
			case MatchResult::POSTPONED:
			case MatchResult::AWAY_WIN_BY_FORFEIT:
			case MatchResult::HOME_WIN_BY_FORFEIT:
				$s_status = 'CANCELLED';
		}

		# hCalendar URL and status
		$o_hcal_para->AddControl(' is ');
		$o_hcal_url = new XhtmlAnchor($s_status, 'http://' . $_SERVER['HTTP_HOST'] . $this->o_match->GetNavigateUrl());
		$o_hcal_url->SetCssClass('url status');
		$o_hcal_para->AddControl($o_hcal_url);
	}

	/**
	 * Creates a scorecard with as much data as is available
	 * @param Match $match
	 * @return void
	 */
	private function CreateScorecard(Match $match)
	{
		require_once("xhtml/tables/xhtml-table.class.php");
		require_once 'stoolball/statistics/stoolball-statistics.class.php';
		$home_batted_first = ($match->Result()->GetHomeBattedFirst() !== false);
		$this->CreateBattingCard($match, $home_batted_first);
		$this->CreateBowlingCard($match, $home_batted_first);
		$this->CreateBattingCard($match, !$home_batted_first);
		$this->CreateBowlingCard($match, !$home_batted_first);
	}

	/**
	 * Creates a batting scorecard for one innings with as much data as is available
	 * @param Match $match
	 * @param bool $is_home_innings
	 * @return void
	 */
	private function CreateBattingCard(Match $match, $is_home_innings)
	{
		$batting_data = $is_home_innings ? $match->Result()->HomeBatting() : $match->Result()->AwayBatting();
		$batting_team = $is_home_innings ? $match->GetHomeTeam() : $match->GetAwayTeam();

		# If no batsmen, only minimal scorecard
		$minimal_scorecard = true;
		$batting_data->ResetCounter();
		while ($batting_data->MoveNext())
		{
			$batting = $batting_data->GetItem();

			/* @var $batting Batting */
			if ($batting->GetPlayer()->GetPlayerRole() == Player::PLAYER)
			{
				$minimal_scorecard = false;
				break;
			}
		}

		# Get total and wickets
		$total = $is_home_innings ? $match->Result()->GetHomeRuns() : $match->Result()->GetAwayRuns();
		$wickets = $is_home_innings ? $match->Result()->GetHomeWickets() : $match->Result()->GetAwayWickets();
		if ($wickets == -1) $wickets = "all out";

		# If no total, wickets, or batters, don't show anything
		if ($minimal_scorecard and is_null($total) and is_null($wickets)) return;

		$batting_table = new XhtmlTable();
		$batting_table->SetCaption(htmlentities($batting_team->GetName() . "'s batting", ENT_QUOTES, "UTF-8", false));
		$batting_table->SetCssClass("scorecard batting");

		if (!$minimal_scorecard)
		{
			$score_header = new XhtmlCell(true, "Runs");
			$score_header->SetCssClass("numeric");
			$batting_headings = new XhtmlRow(array("Batsman", "How out", "Bowler", $score_header));
			$batting_headings->SetIsHeader(true);
			$batting_table->AddRow($batting_headings);
		}

		$batting_data->ResetCounter();
		$byes = null;
		$wides = null;
		$no_balls = null;
		$bonus = null;
		while ($batting_data->MoveNext())
		{
			$batting = $batting_data->GetItem();
			/* @var $batting Batting */

			# Grab the scores for extras players to use later
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

			# If it is an extras player, don't create an ordinary row
			if ($batting->GetPlayer()->GetPlayerRole() != Player::PLAYER)
			{
				continue; # continue doesn't work if it's inside the switch
			}

			$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . htmlentities($batting->GetPlayer()->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($batting->GetPlayer()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a>');
			$player->SetCssClass("batter");
			$player->AddAttribute("typeof", "schema:Person");
			$player->AddAttribute("about", $batting->GetPlayer()->GetLinkedDataUri());

			$how = Batting::Text($batting->GetHowOut());
			if ($batting->GetHowOut() == Batting::NOT_OUT) $how = "<strong>" . htmlentities($how, ENT_QUOTES, "UTF-8", false) . "</strong>";
			if (($batting->GetHowOut() == Batting::CAUGHT or $batting->GetHowOut() == Batting::RUN_OUT) and
			!is_null($batting->GetDismissedBy()) and
			$batting->GetDismissedBy()->GetPlayerRole() == Player::PLAYER)
			{
				$how .= ' <span class="playerName" typeof="schema:Person" about="' . htmlentities($batting->GetDismissedBy()->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '">(<a property="schema:name" rel="schema:url" href="' . htmlentities($batting->GetDismissedBy()->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($batting->GetDismissedBy()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a>' . ")</span>";
			}

			$bowled_by = new XhtmlCell(false, (is_null($batting->GetBowler()) or !$batting->GetBowler()->GetPlayerRole() == Player::PLAYER) ? "" : '<span typeof="schema:Person" about="' . htmlentities($batting->GetBowler()->GetLinkedDataUri(), ENT_QUOTES, "UTF-8", false) . '"><a property="schema:name" rel="schema:url" href="' . htmlentities($batting->GetBowler()->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($batting->GetBowler()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a></span>');
			$bowled_by->AddCssClass("bowler");
			$row = new XhtmlRow(array($player, $how, $bowled_by, htmlentities($batting->GetRuns(), ENT_QUOTES, "UTF-8", false)));
			$runs = $row->GetLastCell();
			$runs->SetCssClass("numeric runs");
			$batting_table->AddRow($row);
		}

		if (!is_null($byes) or !$minimal_scorecard) $this->CreateExtrasRow($batting_table, "Byes", "extras", $byes);
		if (!is_null($wides) or !$minimal_scorecard) $this->CreateExtrasRow($batting_table, "Wides", "extras", $wides);
		if (!is_null($no_balls) or !$minimal_scorecard) $this->CreateExtrasRow($batting_table, "No balls", "extras", $no_balls);
		if ($bonus < 0)
		{
			$this->CreateExtrasRow($batting_table, "Penalty runs", "extras", $bonus); # don't show a 0 here, only if actual runs taken away
		}
		else if ($bonus > 0)
		{
			$this->CreateExtrasRow($batting_table, "Bonus runs", "extras", $bonus); # don't show a 0 here, only if actual runs awarded
		}
		$this->CreateExtrasRow($batting_table, "Total", "totals", $total);
		$this->CreateExtrasRow($batting_table, "Wickets", "totals", $wickets);

		$this->AddControl($batting_table);
	}

	/**
	 * Creates an extras/totals row at the bottom of the batting card
	 * @param XhtmlTable $table
	 * @param string $label
	 * @param string $class
	 * @param int $value
	 * @return void
	 */
	private function CreateExtrasRow(XhtmlTable $table, $label, $class, $value)
	{
		$extras_header = new XhtmlCell(true, htmlentities($label, ENT_QUOTES, "UTF-8", false));
		$extras_header->SetColumnSpan(4);
		$extras_data = new XhtmlCell(false, htmlentities($value, ENT_QUOTES, "UTF-8", false));
		$extras_data->SetCssClass("numeric runs");
		$extras_row = new XhtmlRow(array($extras_header, $extras_data));
		$extras_row->SetCssClass($class);
		$table->AddRow($extras_row);
	}

	/**
	 * Creates a bowling scorecard for one innings with as much data as is available
	 * @param Match $match
	 * @param bool $is_home_innings
	 * @return void
	 */
	private function CreateBowlingCard(Match $match, $is_home_innings)
	{
		$overs_data = $is_home_innings ? $match->Result()->AwayOvers() : $match->Result()->HomeOvers();
		$bowling_data = $is_home_innings ? $match->Result()->AwayBowling() : $match->Result()->HomeBowling();
		$bowling_team = $is_home_innings ? $match->GetAwayTeam() : $match->GetHomeTeam();

		# First, bowler's figures
		if ($bowling_data->GetCount())
		{
			$bowling_table = new XhtmlTable();
			$bowling_table->SetCaption(htmlentities($bowling_team->GetName() . "'s bowlers", ENT_QUOTES, "UTF-8", false));
			$bowling_table->SetCssClass("scorecard");

			$over_header = new XhtmlCell(true, '<abbr title="Overs" class="small">Ov</abbr><span class="large">Overs</span>');
			$over_header->SetCssClass("numeric");
			$maidens_header = new XhtmlCell(true, '<abbr title="Maiden overs" class="small">Md</abbr><abbr title="Maiden overs" class="large">Mdns</abbr>');
			$maidens_header->SetCssClass("numeric");
			$runs_header = new XhtmlCell(true, "Runs");
			$runs_header->SetCssClass("numeric");
			$wickets_header = new XhtmlCell(true, '<abbr title="Wickets" class="small">Wk</abbr><abbr title="Wickets" class="large">Wkts</abbr>');
			$wickets_header->SetCssClass("numeric");
			$economy_header = new XhtmlCell(true, '<abbr title="Economy" class="small">Econ</abbr><span class="large">Economy</span>');
			$economy_header->SetCssClass("numeric");
			$average_header = new XhtmlCell(true, '<abbr title="Average" class="small">Avg</abbr><span class="large">Average</span>');
			$average_header->SetCssClass("numeric");
			$strike_header = new XhtmlCell(true, '<abbr title="Strike rate" class="small">S/R</abbr><span class="large">Strike rate</span>');
			$strike_header->SetCssClass("numeric");
			$bowling_headings = new XhtmlRow(array("Bowler", $over_header, $maidens_header, $runs_header, $wickets_header, $economy_header, $average_header, $strike_header));
			$bowling_headings->SetIsHeader(true);
			$bowling_table->AddRow($bowling_headings);

			$bowling_data->ResetCounter();
			while ($bowling_data->MoveNext())
			{
				$bowling = $bowling_data->GetItem();
				/* @var $bowling Bowling */

				$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . htmlentities($bowling->GetPlayer()->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($bowling->GetPlayer()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a>');
				$player->AddCssClass("bowler");
				$player->AddAttribute("typeof", "schema:Person");
				$player->AddAttribute("about", $bowling->GetPlayer()->GetLinkedDataUri());
				$over_data = new XhtmlCell(false, htmlentities($bowling->GetOvers(), ENT_QUOTES, "UTF-8", false));
				$over_data->SetCssClass("numeric");
				$maidens_data = new XhtmlCell(false, htmlentities($bowling->GetMaidens(), ENT_QUOTES, "UTF-8", false));
				$maidens_data->SetCssClass("numeric");
				$runs_data = new XhtmlCell(false, htmlentities($bowling->GetRunsConceded(), ENT_QUOTES, "UTF-8", false));
				$runs_data->SetCssClass("numeric");
				$wickets_data = new XhtmlCell(false, htmlentities($bowling->GetWickets(), ENT_QUOTES, "UTF-8", false));
				$wickets_data->SetCssClass("numeric");
				$economy = StoolballStatistics::BowlingEconomy($bowling->GetOvers(), $bowling->GetRunsConceded());
				$economy_data = new XhtmlCell(false, is_null($economy) ? "&#8211;" : htmlentities($economy, ENT_QUOTES, "UTF-8", false));
				$economy_data->SetCssClass("numeric");
				$average = StoolballStatistics::BowlingAverage($bowling->GetRunsConceded(), $bowling->GetWickets());
				$average_data = new XhtmlCell(false, is_null($average) ? "&#8211;" : htmlentities($average, ENT_QUOTES, "UTF-8", false));
				$average_data->SetCssClass("numeric");
				$strike = StoolballStatistics::BowlingStrikeRate($bowling->GetOvers(), $bowling->GetWickets());
				$strike_data = new XhtmlCell(false, is_null($strike) ? "&#8211;" : htmlentities($strike, ENT_QUOTES, "UTF-8", false));
				$strike_data->SetCssClass("numeric");
				$bowling_row = new XhtmlRow(array($player, $over_data, $maidens_data, $runs_data, $wickets_data, $economy_data, $average_data, $strike_data));
				$bowling_row->GetFirstCell()->SetCssClass("bowler");
				$bowling_table->AddRow($bowling_row);
			}

			$this->AddControl($bowling_table);
		}

		# Now over-by-over
		if ($overs_data->GetCount())
		{
			$overs_table = new XhtmlTable();
			$overs_table->SetCaption(htmlentities($bowling_team->GetName() . "'s bowling, over-by-over", ENT_QUOTES, "UTF-8", false));
			$overs_table->SetCssClass("scorecard bowling overs");

            $wides_header = new XhtmlCell(true, "Wides");
            $wides_header->SetCssClass("numeric");
			$no_balls_header = new XhtmlCell(true, "No balls");
			$no_balls_header->SetCssClass("numeric");
			$runs_in_over_header = new XhtmlCell(true, "Runs");
			$runs_in_over_header->SetCssClass("numeric");
			$total_header = new XhtmlCell(true, "Total");
			$total_header->SetCssClass("numeric");
			$overs_headings = new XhtmlRow(array("Bowler", $wides_header, $no_balls_header, $runs_in_over_header, $total_header));
			$overs_headings->SetIsHeader(true);
			$overs_table->AddRow($overs_headings);

			$overs_data->ResetCounter();
			$total = 0;
			while ($overs_data->MoveNext())
			{
				$bowling = $overs_data->GetItem();
				/* @var $bowling Over */

				$player = new XhtmlCell(false, '<a property="schema:name" rel="schema:url" href="' . htmlentities($bowling->GetPlayer()->GetPlayerUrl(), ENT_QUOTES, "UTF-8", false) . '">' . htmlentities($bowling->GetPlayer()->GetName(), ENT_QUOTES, "UTF-8", false) . '</a>');
				$player->AddCssClass("bowler");
				$player->AddAttribute("typeof", "schema:Person");
				$player->AddAttribute("about", $bowling->GetPlayer()->GetLinkedDataUri());
				$wides_data = new XhtmlCell(false, htmlentities($bowling->GetWides(), ENT_QUOTES, "UTF-8", false));
				$wides_data->SetCssClass("numeric");
                $no_balls_data = new XhtmlCell(false, htmlentities($bowling->GetNoBalls(), ENT_QUOTES, "UTF-8", false));
                $no_balls_data->SetCssClass("numeric");
				$runs_in_over_data = new XhtmlCell(false, htmlentities($bowling->GetRunsInOver(), ENT_QUOTES, "UTF-8", false));
				$runs_in_over_data->SetCssClass("numeric");
				$total += $bowling->GetRunsInOver();
				$total_data = new XhtmlCell(false, htmlentities($total, ENT_QUOTES, "UTF-8", false));
				$total_data->SetCssClass("numeric");
				$bowling_row = new XhtmlRow(array($player, $wides_data, $no_balls_data, $runs_in_over_data, $total_data));
				$bowling_row->GetFirstCell()->SetCssClass("bowler");
				$overs_table->AddRow($bowling_row);
			}

			$this->AddControl($overs_table);
		}
	}
}
?>
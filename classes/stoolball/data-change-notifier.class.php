<?php
require_once('data/date.class.php');
require_once('stoolball/match.class.php');

class DataChangeNotifier
{
	/**
	 * Sitewide settings
	 *
	 * @var SiteSettings
	 */
	private $settings;

	/**
	 * Send email alerts to admin contacts when data is updated
	 *
	 * @param SiteSettings $settings
	 */
	public function __construct(SiteSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Sends a notification that a match has been modified by a public user
	 *
	 * @param Match $o_match
	 * @param User $o_user
	 * @param bool $b_is_new_match
	 * @param bool $b_is_deleted_match
	 */
	public function MatchUpdated(Match $o_match, User $o_user, $b_is_new_match=false, $b_is_deleted_match=false)
	{
		# Don't send email if match added by a trusted administrator
		if ($o_user->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES)) return;

		# Match may have been added to multiple seasons (though this is theoretical because at the time
		# of writing only an admin can add to multiple seasons... and they don't generate emails.)
		# Nevertheless the object model is geared to that possibility and this way the code makes sense
		# of the object model.
		if ($o_match->Seasons()->GetCount())
		{
			$emails_to_send = array();
			foreach ($o_match->Seasons() as $season)
			{
				/* @var $season Season */

				### Find the email address to notify for this season. ###

				# If the competition has a manager, use their email address...
				$email = '';
				if (!is_null($season->GetCompetition()) and $season->GetCompetition()->GetNotificationEmail())
				{
					# ...but don't email the competition manager if they're adding/updating the match!
					if ($season->GetCompetition()->GetNotificationEmail() == $o_user->GetEmail()) continue;

					$email = $season->GetCompetition()->GetNotificationEmail();
				}
				else
				{
					# If there's no competition manager, send to backup email address
					$email = $this->settings->GetMatchUpdatesEmail();
				}

				# Add the current season to the list of seasons to notify that email address about
				if ($email)
				{
					if (!isset($emails_to_send[$email])) $emails_to_send[$email] = array();
					$emails_to_send[$email][] = $season;
				}
			}

			# And now send an email to each address, listing the change for all the seasons they represent
			foreach ($emails_to_send as $email => $seasons)
			{
				$this->SendMatchUpdatedEmail($o_match, $o_user, $b_is_new_match, $b_is_deleted_match, $email, $seasons);
			}
		}
		else
		{
			# If there's no season there's no competition manager, send to backup email address
			$this->SendMatchUpdatedEmail($o_match, $o_user, $b_is_new_match, $b_is_deleted_match, $this->settings->GetMatchUpdatesEmail());
		}
	}

	/**
	 * Helper to build and send the email when a match has been added by a public user
	 *
	 * @param Match $o_match
	 * @param User $o_user
	 * @param bool $b_is_new_match
	 * @param bool $b_is_deleted_match
	 * @param string $s_email
	 * @param Season[] $seasons
	 */
	private function SendMatchUpdatedEmail(Match $o_match, User $o_user, $b_is_new_match, $b_is_deleted_match, $s_email, $seasons=null)
	{
		# text of email
		$s_season_list = '';
		if (is_array($seasons))
		{
			$i_total_seasons = count($seasons);
			for ($i = 0; $i < $i_total_seasons; $i++)
			{
				if ($i == 0) $s_season_list = "'" . $seasons[$i]->GetCompetitionName() . "'";
				else if ($i == ($i_total_seasons-1)) $s_season_list .= " and '" . $seasons[$i]->GetCompetitionName() . "'";
				else $s_season_list .= ", '" . $seasons[$i]->GetCompetitionName() . "'";
			}
		}
		$s_season = $s_season_list ? " in the $s_season_list" : '';
		$s_why = $s_season_list ? $s_season_list : 'matches';

		$new = $b_is_new_match ? 'new ' : '';
		$verb = $b_is_new_match ? 'added' : 'updated';
		if ($b_is_deleted_match) $verb = 'deleted';
		$match_text = ($o_match->GetMatchType() == MatchType::TOURNAMENT) ? 'tournament' : 'match';
		$s_title = html_entity_decode($o_match->GetTitle());
		$s_date = ucfirst($o_match->GetStartTimeFormatted());
		$s_ground = is_object($o_match->GetGround()) ? $o_match->GetGround()->GetNameAndTown() : '';
		$s_notes = $o_match->GetNotes();
		$s_domain = $this->settings->GetDomain();
		$s_url = 'https://' . $s_domain . $o_match->GetNavigateUrl();
		$s_contact_url = 'https://' . $s_domain . $this->settings->GetFolder('Contact');
		$s_home_name = ($o_match->GetMatchType() == MatchType::TOURNAMENT) ? '' : html_entity_decode($o_match->GetHomeTeam()->GetName());
		$s_away_name = ($o_match->GetMatchType() == MatchType::TOURNAMENT) ? '' : html_entity_decode($o_match->GetAwayTeam()->GetName());
		$s_bat_first = (is_null($o_match->Result()->GetHomeBattedFirst())) ? 'Not known which team batted first' : ($o_match->Result()->GetHomeBattedFirst() ? $s_home_name : $s_away_name) . ' batted first';
		$s_home_runs = (is_null($o_match->Result()->GetHomeRuns())) ? '(not known)' : $o_match->Result()->GetHomeRuns();
		$s_home_wickets = (is_null($o_match->Result()->GetHomeWickets())) ? '(not known)' : $o_match->Result()->GetHomeWickets();
		if ($s_home_wickets == -1)
		{
			$s_home_wickets = 'all out';
		}
		else
		{
			$s_home_wickets = 'for ' . $s_home_wickets . ' wickets';
		}
		$s_away_runs = (is_null($o_match->Result()->GetAwayRuns())) ? '(not known)' : $o_match->Result()->GetAwayRuns();
		$s_away_wickets = (is_null($o_match->Result()->GetAwayWickets())) ? '(not known)' : $o_match->Result()->GetAwayWickets();
		if ($s_away_wickets == -1)
		{
			$s_away_wickets = 'all out';
		}
		else
		{
			$s_away_wickets = 'for ' . $s_away_wickets . ' wickets';
		}
		$s_user = $o_user->GetName();

		$s_body = wordwrap("A {$new}$match_text has been $verb on the Stoolball England website at {$s_domain}{$s_season}.\n\n" .

		"The $match_text was $verb by $s_user.\n\n" .

		"The $match_text details are as follows:\n\n" .
		"    $s_title\n" .
		"    $s_date\n" .
		"    $s_ground");

		if ($s_notes)
		{
			$s_notes = "\n\n" . wordwrap($s_notes, 70);
			$s_notes = str_replace("\n", "\n    ", $s_notes);
			$s_body .= $s_notes;
		}

		if ($o_match->GetStartTime() <= gmdate('U') and !$b_is_new_match and $o_match->GetMatchType() != MatchType::TOURNAMENT)
		{
			$s_body .= <<<EMAILBODY


	$s_bat_first
EMAILBODY;
			if ($o_match->Result()->GetHomeBattedFirst() === false)
			{
				$s_body .= <<<EMAILBODY

	$s_away_name score: $s_away_runs runs $s_away_wickets
	$s_home_name score: $s_home_runs runs $s_home_wickets
EMAILBODY;
}
else
{
	$s_body .= <<<EMAILBODY

	$s_home_name score: $s_home_runs runs $s_home_wickets
	$s_away_name score: $s_away_runs runs $s_away_wickets
EMAILBODY;
}
}

$s_body .= "\n\n";
if (!$b_is_deleted_match) $s_body .= wordwrap("You can view the $match_text at $s_url\n\n");

$s_body .= wordwrap("You have received this email because you are the administrative contact for $s_why on $s_domain.\n\n" .

"We let you know when a $match_text is $verb by a member of the public, so that you can check there's nothing wrong.\n\n" .

"If this email has been sent to the wrong address, or if the $match_text details are wrong, please let us know using the contact form at $s_contact_url.\n\n");


# send email, copy to me
require_once 'Zend/Mail.php';
$o_email = new Zend_Mail('UTF-8');
$o_email->addTo($s_email);
$o_email->setFrom('alerts@stoolball.org.uk', 'Stoolball England alerts');
$o_email->setSubject(ucfirst($match_text) . " $verb: $s_title, $s_date");
$o_email->setBodyText($s_body);
try
{
	$o_email->send();
}
catch (Zend_Mail_Transport_Exception $e)
{
	# Do nothing - failure to send this email should not be a fatal error
}
	}

}
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	private $i_match_id;
	private $i_team_id;
	private $i_season_id;
    private $tournament_player_type;
	private $s_matches;
	private $s_cal_title;
	private $s_cal_url;

	public function OnSiteInit()
	{
		# check parameter
		if (isset($_GET['match']) and is_numeric($_GET['match']))
		{
			$this->i_match_id = (int)$_GET['match'];
			$this->s_matches = 'match';
		}
		else if (isset($_GET['team']) and is_numeric($_GET['team']))
		{
			$this->i_team_id = (int)$_GET['team'];
			$this->s_matches = 'matches';
		}
		else if (isset($_GET['season']) and is_numeric($_GET['season']))
		{
			$this->i_season_id = (int)$_GET['season'];
			$this->s_matches = 'matches';
		}
        else if (isset($_GET['tournaments']) and $_GET['tournaments']) 
        {
            $this->tournament_player_type = preg_replace('/[^a-z]/',"",$_GET['tournaments']);
        }
		else
		{
			header('Location: /play/');
			exit();
		}

		parent::OnSiteInit();
	}

	public function OnLoadPageData()
	{
		if (!is_null($this->i_match_id))
		{
            require_once('stoolball/match-manager.class.php');
			$o_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
			$o_manager->ReadByMatchId(array($this->i_match_id));
			$o_match = $o_manager->GetFirst();
			if ($o_match instanceof Match)
			{
				$this->s_cal_title = $o_match->GetTitle() . ' &#8211; ' . $o_match->GetStartTimeFormatted();
				$this->s_cal_url = $o_match->GetCalendarNavigateUrl();
			}
			unset($o_manager);
		}
		else if (!is_null($this->i_team_id))
		{
            require_once('stoolball/team-manager.class.php');
			$o_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
			$o_manager->ReadById(array($this->i_team_id));
			$o_team = $o_manager->GetFirst();
			if ($o_team instanceof Team)
			{
				$this->s_cal_title = $o_team->GetName() . '\'s current season';
				$this->s_cal_url = $o_team->GetCalendarNavigateUrl();
			}
			unset($o_manager);
		}
		else if (!is_null($this->i_season_id))
		{
            require_once('stoolball/competition-manager.class.php');
			$o_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
			$o_manager->ReadById(null, array($this->i_season_id));
			$o_comp = $o_manager->GetFirst();
			if ($o_comp instanceof Competition)
			{
				$o_season = $o_comp->GetWorkingSeason();
				$this->s_cal_title = $o_season->GetCompetitionName();
				$this->s_cal_url = $o_season->GetCalendarNavigateUrl();
			}
			unset($o_manager);
		}
        else if ($this->tournament_player_type) 
        {
            $this->s_matches = $this->tournament_player_type . " tournaments";
            $this->s_cal_url = "/tournaments/" . $this->tournament_player_type . "/calendar";
        }

		if (is_null($this->s_cal_url))
		{
			header('Location: /play/');
			exit();
		}
	}

	public function OnPrePageLoad()
	{
	    $title = 'Add ' . $this->s_matches . ' to your calendar';
        if ($this->s_cal_title) $title .= ': ' . $this->s_cal_title;
		$this->SetPageTitle($title);
		$this->SetContentConstraint($this->ConstrainText());
	}

	public function OnPageLoad()
	{
        $title = 'Add ' . $this->s_matches . ' to your calendar';
        if ($this->s_cal_title) $title .= ': ' . $this->s_cal_title;
		echo '<h1>' . htmlentities($title, ENT_QUOTES, "UTF-8", false) . '</h1>';

        $subscribe_link = new XhtmlAnchor(null, "webcal://" . $_SERVER['HTTP_HOST'] . $this->s_cal_url . '.ics');
        $subscribe_link->SetCssClass('calendar');
        $subscribe_link->AddAttribute('type', 'text/calendar');
        $subscribe_link->AddAttribute('rel', 'nofollow');

		$download_link = new XhtmlAnchor('download the calendar', "https://" . $_SERVER['HTTP_HOST'] . $this->s_cal_url . '.ics');
		$download_link->AddAttribute('type', 'text/calendar');
		$download_link->AddAttribute('rel', 'nofollow');

		$subscribe_link->AddControl(new XhtmlElement('p', 'Subscribe to calendar', 'subscribe'));
		$subscribe_link->AddControl(new XhtmlElement('p', Html::Encode($this->s_cal_title ? $this->s_cal_title : ucfirst($this->s_matches)),"subscribe-to medium large"));
		echo $subscribe_link;
        ?>
        <p>If you run your life on your computer or mobile, you can save time by adding
		all of your stoolball matches to your calendar, kept up-to-date automatically.</p>

        <h2>How to subscribe</h2>
        <p>You just need a calendar which supports the iCalendar standard. Most do, including popular 
                    ones like Microsoft Outlook, Apple iCal and Google Calendar.</p>
        
        <ul>
        <li><a href="http://www.apple.com/findouthow/mac/#subscribeical">Apple iCal: Subscribe to an iCal Calendar</a></li>
        <li><a href="http://office.microsoft.com/en-us/outlook-help/view-and-subscribe-to-internet-calendars-HA010167325.aspx#BM2">Microsoft Outlook: Add an Internet calendar subscription</a></li>
        <li><a href="http://support.google.com/calendar/bin/answer.py?hl=en&amp;answer=37100">Google Calendar: Subscribe to calendars</a></li>
        <li><a href="http://windows.microsoft.com/en-GB/hotmail/calendar-subscribe-calendar-ui">Hotmail Calendar: Import or subscribe to a calendar</a></li>
        </ul>
        
        <h2>How to download</h2>
        <p>If you don't want to subscribe to updates, <?php echo $download_link;?> instead.</p>
        <?php 
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
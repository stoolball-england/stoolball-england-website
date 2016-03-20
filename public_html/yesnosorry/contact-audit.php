<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../' . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once ('page/stoolball-page.class.php');
require_once ('stoolball/player.class.php');

class CurrentPage extends StoolballPage
{
	private $teams;
    private $competitions;
    
	function OnLoadPageData()
	{
        require_once ("stoolball/team-manager.class.php");
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->FilterByActive(true);
		$team_manager->ReadById();
		$this->teams = $team_manager->GetItems();

        require_once ("stoolball/competition-manager.class.php");
        $comp_manager = new CompetitionManager($this->GetSettings(), $this->GetDataConnection());
        $comp_manager->SetExcludeInactive(true);
        $comp_manager->ReadById();
        $this->competitions = $comp_manager->GetItems();
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle('Contact details audit');
        #$this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

	function OnPageLoad()
	{
		?>
		<h1>Contact details audit</h1>

        <p>All current teams (except representative teams) should have a phone number, email address and postcode. 
            All current competitions should have a phone number and email address. This is a list of those that haven't.</p>

        <p>One benefit of having both phone and email is that we can publicise the team automatically on services like BBC Get Inspired, which demand both.</p>

        <p><strong>Do not publish contact details without explicit permission as that would be against the Data Protection Act.</strong></p>
		<table>
		    <caption>Teams</caption>
		    <thead><tr><th>Team</th><th>Phone</th><th>Email</th><th>Postcode</th><th>Private contact</th></tr></thead>
		    <tbody>
		<?php
		foreach ($this->teams as $team) {
		    /* @var $team Team */
		    $phone = Html::Encode($team->GetContactPhone());
            $email = Html::Encode($team->GetContactEmail());
            $postcode = Html::Encode($team->GetGround()->GetAddress()->GetPostcode());
            $is_representative = $team->GetTeamType() == Team::REPRESENTATIVE;
            $private = $team->GetPrivateContact() ? "yes" : "no";
            if ((!$phone or !$email or !$postcode) and !$is_representative) {
		      echo "<tr><td><a href=\"" . Html::Encode($team->GetNavigateUrl()) . "\">" . Html::Encode($team->GetName()) . "</a></td><td>$phone</td><td>$email</td><td>$postcode</td><td>$private</tr>";
            }
		}
        ?>
            </tbody>
        </table>

        <table>
            <caption>Competitions</caption>
            <thead><tr><th>Competitions</th><th>Phone</th><th>Email</th></tr></thead>
            <tbody>
        <?php
        foreach ($this->competitions as $competition) {
            /* @var $competition Competition */
            $phone = Html::Encode($competition->GetContactPhone());
            $email = Html::Encode($competition->GetContactEmail());
            if (!$phone or !$email) {
              echo "<tr><td><a href=\"" . Html::Encode($competition->GetNavigateUrl()) . "\">" . Html::Encode($competition->GetName()) . "</a></td><td>" . $phone . "</td><td>" . $email . "</td></tr>";
            }
        }
        ?>
            </tbody>
        </table>
        <?php
	}

}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_TEAMS, false);
?>
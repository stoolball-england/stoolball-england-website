<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/ground-manager.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/match-list-control.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The ground to show
	 *
	 * @var Ground
	 */
	private $ground;

	/** The matches to display 
	 * @var Match[]
	*/
	private $matches;

	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();

		# new data managers
		$ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());
		$match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# get ground
		$ground_manager->ReadById(array($_GET['item']));
		$this->ground = $ground_manager->GetFirst();
		unset($ground_manager);

		# must have found a ground
		if (!$this->ground instanceof Ground) $this->Redirect();

		# get matches at the ground this season
		$a_season_dates = Season::SeasonDates();
		$match_manager->FilterByDateStart($a_season_dates[0]);
		$match_manager->FilterByGround(array($this->ground->GetId()));
		$match_manager->ReadMatchSummaries();
		$this->matches = $match_manager->GetItems();
		unset($match_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle("Matches at " . $this->ground->GetNameAndTown());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->SetPageDescription("Stoolball matches at " . $this->ground->GetNameAndTown());
	}

	function OnPageLoad()
	{
		?>
		<h1>Matches at <?php echo Html::Encode($this->ground->GetNameAndTown()); ?></h1>
		<?php
        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => $this->ground->GetNavigateUrl(), 'Matches' => '', 'Statistics' => $this->ground->GetStatsNavigateUrl());       
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
 
 		if (count($this->matches))
		{
			echo new MatchListControl($this->matches);
		}
		else
		{
			?>
			<p>Sorry, there are no matches to show you.</p>
			<p>If you know of a match which should be listed, please <a href="/play/manage/website/">add the match to the website</a>.</p>
			<?php
		}
 		?>
        </div>
        </div>
		<?php 
		if (is_array($this->matches) and count($this->matches))
		{
			$this->AddSeparator();

			require_once('stoolball/user-edit-panel.class.php');
			$o_user = new UserEditPanel($this->GetSettings(), 'this ground');
			$o_user->AddCssClass("with-tabs");
			$o_user->AddLink('add matches to your calendar', $this->ground->GetCalendarUrl());
			echo $o_user;
		}				
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
	private $statistics = array();

	function OnLoadPageData()
	{
		$this->statistics["querystring"] = "";
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		require_once("_summary-data-query.php");
		unset($statistics_manager);
	}

	function OnPrePageLoad()
	{
		# set up page
		$this->SetPageTitle("Stoolball statistics for all teams");
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
	}

	function OnPageLoad()
	{
		?>
<h1>Statistics for all teams</h1>

<p>These are the best performances in all stoolball, based on scorecards added to this website. If you know someone who did better, you can add the scorecard for that match &#8211; see <a
	href="/play/manage/website/how-to-add-match-results/">How to add match results</a>.</p>

		<?php
		require_once("_summary-data-found.php");
		require_once("_summary-controls.php");
		$this->AddSeparator();
	$this->BuySomething();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/ground-manager.class.php');
require_once('person/postal-address-control.class.php');
require_once('stoolball/user-edit-panel.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The ground to show
	 *
	 * @var Ground
	 */
	private $ground;
	private $best_batting;
	private $best_bowling;
	private $most_runs;
	private $most_wickets;
	private $most_catches;
	private $has_player_stats;

	public function OnSiteInit()
	{
		$this->SetHasGoogleMap(true);
		parent::OnSiteInit();
	}

	function OnLoadPageData()
	{
		# check parameter
		if (!isset($_GET['item']) or !is_numeric($_GET['item'])) $this->Redirect();

		# new data managers
		$ground_manager = new GroundManager($this->GetSettings(), $this->GetDataConnection());

		# get ground
		$ground_manager->ReadById(array($_GET['item']));
		$this->ground = $ground_manager->GetFirst();

		# must have found a ground
		if (!$this->ground instanceof Ground) $this->Redirect();

        # Get teams based at the ground
		require_once("stoolball/team-manager.class.php");
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
		$team_manager->FilterByActive(true) ;
		$team_manager->FilterByGround(array($this->ground->GetId()));
        $team_manager->FilterByTeamType(array(Team::CLOSED_GROUP, Team::OCCASIONAL, Team::REGULAR, Team::REPRESENTATIVE, Team::SCHOOL_YEARS, Team::SCHOOL_CLUB, Team::SCHOOL_OTHER));
		$team_manager->ReadTeamSummaries();
		$this->ground->Teams()->SetItems($team_manager->GetItems());

        # Update search engine
        if ($this->ground->GetSearchUpdateRequired())
        { 
            require_once ("search/ground-search-adapter.class.php");
            $this->SearchIndexer()->DeleteFromIndexById("ground" . $this->ground->GetId());
            $adapter = new GroundSearchAdapter($this->ground);
            $this->SearchIndexer()->Index($adapter->GetSearchableItem());
            $this->SearchIndexer()->CommitChanges();
            
            $ground_manager->SearchUpdated($this->ground->GetId());
        }
        unset($team_manager);
		unset($ground_manager);
        
		# Read statistics highlights for the ground
		require_once('stoolball/statistics/statistics-manager.class.php');
		$statistics_manager = new StatisticsManager($this->GetSettings(), $this->GetDataConnection());
		$statistics_manager->FilterByGround(array($this->ground->GetId()));
		$statistics_manager->FilterMaxResults(1);
		$this->best_batting = $statistics_manager->ReadBestBattingPerformance();
		$this->best_bowling = $statistics_manager->ReadBestBowlingPerformance();
		$this->most_runs = $statistics_manager->ReadBestPlayerAggregate("runs_scored");
		$this->most_wickets = $statistics_manager->ReadBestPlayerAggregate("wickets", true);
		$this->most_catches = $statistics_manager->ReadBestPlayerAggregate("catches", true);

		# See what stats we've got available
		$best_batting_count = count($this->best_batting);
		$best_bowling_count = count($this->best_bowling);
		$best_batters = count($this->most_runs);
		$best_bowlers = count($this->most_wickets);
		$best_catchers = count($this->most_catches);
		$this->has_player_stats = ($best_batting_count or $best_batters or $best_bowling_count or $best_bowlers or $best_catchers);

		if (!$this->has_player_stats)
		{
			$player_of_match = $statistics_manager->ReadBestPlayerAggregate("player_of_match");
			$this->has_player_stats = (bool)count($player_of_match);
		}
		unset($statistics_manager);
	}

	function OnPrePageLoad()
	{
		$this->SetPageTitle($this->ground->GetNameAndTown());
		$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		$this->LoadClientScript('/scripts/maps-3.js');
		$this->LoadClientScript('ground.js', true);
		
		$description = $this->ground->GetNameAndTown();
		$teams = $this->ground->Teams()->GetItems();
		$teams_count = count($teams);
		if ($teams_count)
		{
			$description .= " is home to ";
			for ($i = 0; $i < $teams_count; $i++) 
			{
				$description .= $teams[$i]->GetName();
				if ($i < ($teams_count-2)) $description .= ", ";
				if ($i == ($teams_count-2)) $description .= " and ";
			}
			$description .= ".";	
		} 
		else 
		{
			$description .= " is not currently home to any teams.";
		}
		$this->SetPageDescription($description);
	}

	function OnPageLoad()
	{
		echo '<div class="ground vcard" typeof="schema:Place" about="' . $this->ground->GetLinkedDataUri() . '">';
		
		$o_fn = new XhtmlElement('h1', htmlentities($this->ground->GetNameAndTown(), ENT_QUOTES, "UTF-8", false));
		$o_fn->SetCssClass('fn');
		$o_fn->AddAttribute("property", "schema:name");
		echo $o_fn;

        require_once('xhtml/navigation/tabs.class.php');
        $tabs = array('Summary' => '', 'Statistics' => $this->ground->GetStatsNavigateUrl());       
        echo new Tabs($tabs);
        
        ?>
        <div class="box tab-box">
            <div class="dataFilter"></div>
            <div class="box-content">
        <?php
 
		$address = new XhtmlElement("div");
		$address->AddAttribute("rel", "schema:address");
		$address->AddAttribute("resource", $this->ground->GetLinkedDataUri() . "#PostalAddress");
		$postal = new PostalAddressControl($this->ground->GetAddress());
		$postal->AddAttribute("about", $this->ground->GetLinkedDataUri() . "#PostalAddress");
		$address->AddControl($postal);
		echo $address;

		# Show teams based at this ground
		if ($this->ground->Teams()->GetCount())
		{
			require_once("stoolball/team-list-control.class.php");
			echo "<h2>Teams based at this ground</h2>" . new TeamListControl($this->ground->Teams()->GetItems());
		} 

		if (!is_null($this->ground->GetAddress()->GetLatitude()) and !is_null($this->ground->GetAddress()->GetLongitude()))
		{
			$o_geo = new XhtmlElement('div');
			$o_geo->SetXhtmlId('geoGround');
			$o_geo->AddAttribute("rel", "schema:geo");
			$o_geo->AddAttribute("resource", $this->ground->GetLinkedDataUri() . "#geo");

			$o_latlong = new XhtmlElement('p');
			$o_latlong->SetCssClass('geo'); # geo microformat
			$o_latlong->AddAttribute("about", $this->ground->GetLinkedDataUri() . "#geo");
			$o_latlong->AddAttribute("typeof", "schema:GeoCoordinates");
			$o_latlong->AddControl('Latitude ');
			$o_lat = new XhtmlElement('span', (string)$this->ground->GetAddress()->GetLatitude());
			$o_lat->SetCssClass('latitude'); # geo microformat
			$o_lat->AddAttribute("property", "schema:latitude");
			$o_latlong->AddControl($o_lat);
			$o_latlong->AddControl('; longitude ');
			$o_long = new XhtmlElement('span', (string)$this->ground->GetAddress()->GetLongitude());
			$o_long->SetCssClass('longitude'); # geo microformat
			$o_long->AddAttribute("property", "schema:longitude");
			$o_latlong->AddControl($o_long);
			$o_geo->AddControl($o_latlong);

			$s_place = '';
			$s_class = '';
			switch ($this->ground->GetAddress()->GetGeoPrecision())
			{
				case GeoPrecision::Exact():
					$s_place = $this->ground->GetNameAndTown();
					$s_class = 'exact';
					break;
				case GeoPrecision::Postcode():
					$s_place = $this->ground->GetAddress()->GetPostcode();
					$s_class = 'postcode';
					break;
				case GeoPrecision::StreetDescriptor():
					$s_place = $this->ground->GetAddress()->GetStreetDescriptor() . ', ' . $this->ground->GetAddress()->GetTown();
					$s_class = 'street';
					break;
				case GeoPrecision::Town():
					$s_place = $this->ground->GetAddress()->GetTown();
					$s_class = 'town';
					break;
			}
			$o_map_link = new XhtmlAnchor('Map of <span class="' . $s_class . '">' . htmlentities($s_place, ENT_QUOTES, "UTF-8", false) . '</span> on Google Maps', 
			                 'http://maps.google.co.uk/?z=16&amp;q=' . urlencode($this->ground->GetNameAndTown()) . '@' . $this->ground->GetAddress()->GetLatitude() . ',' . $this->ground->GetAddress()->GetLongitude() . '&amp;ll=' . $this->ground->GetAddress()->GetLatitude() . ',' . $this->ground->GetAddress()->GetLongitude());

			$o_map = new XhtmlElement('div', $o_map_link);
			$o_geo->AddControl($o_map);

			echo $o_geo;
		}

		if ($this->ground->GetDirections())
		{
			echo new XhtmlElement('h2', 'Directions');
            $s_directions = htmlentities($this->ground->GetDirections(), ENT_QUOTES, "UTF-8", false);
			$s_directions = XhtmlMarkup::ApplyCharacterEntities($s_directions);
			$s_directions = XhtmlMarkup::ApplyParagraphs($s_directions);
			$s_directions = XhtmlMarkup::ApplySimpleTags($s_directions);
			echo $s_directions;
		}

		if ($this->ground->GetParking())
		{
			echo new XhtmlElement('h2', 'Parking');
            $s_parking = htmlentities($this->ground->GetParking(), ENT_QUOTES, "UTF-8", false);
			$s_parking = XhtmlMarkup::ApplyCharacterEntities($s_parking);
			$s_parking = XhtmlMarkup::ApplyParagraphs($s_parking);
			$s_parking = XhtmlMarkup::ApplySimpleTags($s_parking);
			$s_parking = XhtmlMarkup::ApplyLinks($s_parking);
			echo $s_parking;
		}

		if ($this->ground->GetFacilities())
		{
			echo new XhtmlElement('h2', 'Facilities');
            $s_facilities = htmlentities($this->ground->GetFacilities(), ENT_QUOTES, "UTF-8", false);
			$s_facilities = XhtmlMarkup::ApplyCharacterEntities($s_facilities);
			$s_facilities = XhtmlMarkup::ApplyParagraphs($s_facilities);
			$s_facilities = XhtmlMarkup::ApplySimpleTags($s_facilities);
			$s_facilities = XhtmlMarkup::ApplyLinks($s_facilities);
			echo $s_facilities;
		}

		$o_meta = new XhtmlElement('p');
		$o_meta->SetCssClass('metadata');

		$o_meta->AddControl('Status: ');

		$o_uid = new XhtmlElement('span', $this->ground->GetLinkedDataUri());
		$o_uid->SetCssClass('uid');
		$o_meta->AddControl($o_uid);

		$o_meta->AddControl(' last updated at ');
		$o_rev = new XhtmlElement('abbr', Date::BritishDateAndTime($this->ground->GetDateUpdated()));
		$o_rev->SetTitle(Date::Microformat($this->ground->GetDateUpdated()));
		$o_rev->SetCssClass('rev');
		$o_meta->AddControl($o_rev);

		$o_meta->AddControl(', sort as ');
		$o_url = new XhtmlAnchor(htmlentities($this->ground->GetAddress()->GenerateSortName(), ENT_QUOTES, "UTF-8", false), $this->ground->GetNavigateUrl());
		$o_url->SetCssClass('url sort-string');
		$o_meta->AddControl($o_url);

		echo $o_meta;
		echo "</div>";
        ?>
        </div>
        </div>
        <?php 

		$this->AddSeparator();

		$o_user = new UserEditPanel($this->GetSettings(), 'this ground');
        $o_user->AddCssClass("with-tabs");
		if (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_GROUNDS))
		{
			$o_user->AddLink("edit this ground", $this->ground->GetEditGroundUrl());
			$o_user->AddLink("delete this ground", $this->ground->GetDeleteGroundUrl());
		}
		
		echo $o_user;

		# Show top players
		if ($this->has_player_stats)
		{
			require_once('stoolball/statistics-highlight-table.class.php');
			echo new StatisticsHighlightTable($this->best_batting, $this->most_runs, $this->best_bowling, $this->most_wickets, $this->most_catches, "All seasons");
		}
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
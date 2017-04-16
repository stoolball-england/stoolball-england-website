<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');
require_once('stoolball/match-list-control.class.php');
require_once('xhtml/forms/xhtml-form.class.php');
require_once('xhtml/forms/xhtml-select.class.php');

class CurrentPage extends StoolballPage
{
	private $a_matches;
	private $a_months;
    private $i_start;
    private $i_end;
    private $rss_url;

	function OnLoadPageData()
	{
		# new data manager
		$manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		# Check for month value
		if (isset($_GET['month']) and is_numeric($_GET['month']))
		{
			# Make sure it looks real
			$i_day = (int)date('d', $_GET['month']);
			$i_year = (int)date('Y', $_GET['month']);
			$i_month = (int)date('m', $_GET['month']);

			if ($i_year >= 2000 and $i_year <= 2050 and $i_month >= 1 and $i_month <= 12 and $i_day == 1)
			{
				# Read start date specified by user, which will be with DST applied because users don't think in UTC
				$this->i_start = $_GET['month'];
				$this->i_end = mktime(11, 59, 59, $i_month, date('t', $_GET['month']), $i_year);

				# Convert to UTC, as that's how match dates are stored
				$this->i_end = gmdate('U', $this->i_end);

                $manager->FilterByDateStart($this->i_start);
                $manager->FilterByDateEnd($this->i_end);
			}
		}
        else 
        {
            # get next few matches
            $i_one_day = 86400;
            $this->i_start = gmdate('U')-($i_one_day*1); # yesterday
            $manager->FilterByDateStart($this->i_start);
            $manager->FilterByMaximumResults(50);
        }

		# Check for match type
		$i_match_type = null;
		if (isset($_GET['type']) and is_numeric($_GET['type']))
		{
			$i_match_type = (int)$_GET['type'];
			if ($i_match_type < 0 or $i_match_type > 50) $i_match_type = null;
		}
		$a_match_types = is_null($i_match_type) ? null : array($i_match_type);
        $manager->FilterByMatchType($a_match_types);

		# Check for player type
		$i_player_type = null;
		if (isset($_GET['player']) and is_numeric($_GET['player']))
		{
			$i_player_type = (int)$_GET['player'];
			if ($i_player_type < 0 or $i_player_type > 50) $i_player_type = null;
		}
		$a_player_types = is_null($i_player_type) ? null : array($i_player_type);
        $manager->FilterByPlayerType($a_player_types);

		$manager->ReadMatchSummaries();
		$this->a_matches = $manager->GetItems();

		$this->a_months = $manager->ReadMatchMonths();

		# tidy up
		unset($manager);
	}

	function OnPrePageLoad()
	{
        $this->SetPageTitle('Stoolball matches');

		$this->SetContentConstraint(StoolballPage::ConstrainBox());
	}

    function OnCloseHead()
    {  
        $rss_url = "";
        if (isset($_GET['player']) and $_GET['player'] and $_GET['player'] !== 'all') {
            $rss_url = 'player=' . Html::Encode($_GET['player']);
        } 
        if (isset($_GET['type']) and $_GET['type']) {
            if ($rss_url) $rss_url .= '&amp;'; 
           $rss_url .= 'type=' . Html::Encode($_GET['type']);
        }
        if ($this->i_start) {
            if ($rss_url) $rss_url .= '&amp;'; 
            $rss_url .= 'from=' . Html::Encode(gmdate("Y-m-d", $this->i_start));             
        }
        if ($this->i_end) {
            if ($rss_url) $rss_url .= '&amp;'; 
            $rss_url .= 'to=' . Html::Encode(gmdate("Y-m-d", $this->i_end)); 
        }
                 
        if ($rss_url) $rss_url = '?' . $rss_url;
        $this->rss_url = Html::Encode("https://" . $_SERVER['HTTP_HOST'] .'/matches.rss' . $rss_url);
        
        ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo Html::Encode($this->GetPageTitle()); ?> RSS feed" href="<?php echo $this->rss_url; ?>" />
        <?php
        parent::OnCloseHead();
    }

	function OnPageLoad()
	{
        echo "<h1>Stoolball matches</h1>";
		
		# Filter controls
		$filter_box = new XhtmlForm();
		$filter_box->SetCssClass('dataFilter');
		$filter_box->AddAttribute('method', 'get');

		$filter_box->AddControl(new XhtmlElement('p','Show me: ',"follow-on large"));
		
		$filter_inner = new XhtmlElement('div');
		$filter_box->AddControl($filter_inner);

		$gender = new XhtmlSelect('player', 'Player type');
		$gender->SetHideLabel(true);
		$gender->AddControl(new XhtmlOption("mixed and ladies", ''));
		$gender->AddControl(new XhtmlOption('mixed', 1));
		$gender->AddControl(new XhtmlOption("ladies", 2));
		if (isset($_GET['player'])) $gender->SelectOption($_GET['player']);
		$filter_inner->AddControl($gender);

		$type = new XhtmlSelect('type', 'Match type');
		$type->SetHideLabel(true);
		$type->AddControl(new XhtmlOption('all matches', ''));
		$type->AddControl(new XhtmlOption('league matches', MatchType::LEAGUE));
		$type->AddControl(new XhtmlOption('cup matches', MatchType::CUP));
		$type->AddControl(new XhtmlOption('friendlies', MatchType::FRIENDLY));
		$type->AddControl(new XhtmlOption('tournaments', MatchType::TOURNAMENT));
		$type->AddControl(new XhtmlOption('practices', MatchType::PRACTICE));
		if (isset($_GET['type'])) $type->SelectOption($_GET['type']);
		$filter_inner->AddControl($type);

		$filter_inner->AddControl(' in ');

		$month = new XhtmlSelect('month', 'Month');
		$month->SetHideLabel(true);
		$month->AddControl(new XhtmlOption('next few matches', ''));
		foreach ($this->a_months as $i_month => $i_matches)
		{
			$opt = new XhtmlOption(Date::MonthAndYear($i_month), $i_month);
			if (isset($_GET['month']) and $_GET['month'] == $i_month) $opt->AddAttribute('selected', 'selected');
			$month->AddControl($opt);
			unset($opt);
		}
		$filter_inner->AddControl($month);

		$update = new XhtmlElement('input');
		$update->AddAttribute('type', 'submit');
		$update->AddAttribute('value', 'Update');
		$filter_inner->AddControl($update);

		# Content container
        $container = new XhtmlElement('div', $filter_box, "box");
		$content = new XhtmlElement("div",null, "box-content");
        $container->AddControl($content);
        
		# Display the matches
		if (count($this->a_matches))
		{
			$list = new MatchListControl($this->a_matches);
			$content->AddControl($list);
		}
		else
		{
			$content->AddControl(new XhtmlElement('p', 'Sorry, there are no matches to show you.'));
			$content->AddControl(new XhtmlElement('p', 'If you know of a match which should be listed, please <a href="/play/manage/website/">add the match to the website</a>.'));
		}

		echo $container;
        
        ?>
        <form method='post' action='http://blogtrottr.com' class="rss-form">
            <h2>Get alerts about new and updated matches like these <small>(uses <a href="http://blogtrottr.com">blogtrottr.com</a>)</small></h2>
            <label for="rss-email">Your email:</label>
            <input type='email' name='btr_email' id="rss-email" required="required" />
            <input type='hidden' name='btr_url' value='<?php echo $this->rss_url; ?>' />
            <input type='hidden' name='schedule_type' value='0' />
            <input type='submit' value='Subscribe' />
            <p><a href="<?php echo $this->rss_url; ?>" rel="alternate" type="application/rss+xml" class="rss">Subscribe by RSS</a></p>
        </form>

        <?php
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
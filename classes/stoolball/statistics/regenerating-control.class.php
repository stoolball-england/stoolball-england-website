<?php
namespace Stoolball\Statistics;

require_once("xhtml/placeholder.class.php");

/**
 * Message to display for a player while statistics are being regenerated
 */
class RegeneratingControl extends \Placeholder {
        
	private $extras;
    private $title;
    
    public function __construct() {
        $url = $_SERVER['REQUEST_URI'];
        $len = strlen($url);
        $no_balls = (substr($url, $len-9,9) == "/no-balls");
        $wides = (substr($url, $len-6, 6) == "/wides");
        $byes = (substr($url, $len-5,5) == "/byes");
        $bonus = (substr($url, $len-11, 11) == "/bonus-runs");
        if ($no_balls or $wides or $byes or $bonus)
        {
            if ($no_balls) $this->extras = "no balls";
            if ($wides) $this->extras = "wides";
            if ($byes) $this->extras = "byes";
            if ($bonus) $this->extras = "bonus runs";
            $this->title = "Add statistics for this team";
        }
        else {
            $this->title = "Please come back later";
        }
    }
    
    public function OnPreRender() {
        if ($this->extras)
        {
            $this->AddControl("<h1>$this->title</h1>
            <p>
            There aren't any $this->extras recorded yet for this team.
            </p>
            <p>
            To find out how to add statistics, see <a href=\"/play/manage/website/matches-and-results-why-you-should-add-yours/\">Matches and results &#8211; why you should add yours</a>.
            </p>");
        }
        else
        {
            $this->AddControl("<h1>$this->title</h1>
            <p>We're working on something new, and the statistics for this player aren't ready yet.</p>
            <p>Please come back later and see whether you can spot what we've done!</p>");
        }
    }
    
    public function GetPageTitle() {
        return $this->title;
    }
}

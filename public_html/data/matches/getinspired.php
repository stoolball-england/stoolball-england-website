<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

require_once('page/page.class.php');
require_once("context/stoolball-settings.class.php");

class CurrentPage extends Page
{
	function OnLoadPageData()
	{
	    # Require an API key to include personal contact details to avoid spam bots picking them up
        $api_keys = $this->GetSettings()->GetApiKeys();
        $valid_api_key = false;
        if (isset($_GET['key']) and in_array($_GET['key'], $api_keys)) 
        {
            $valid_api_key = true;
        }
        
        # Get match data, and the home teams for their contact details
        # Note that this feed requires an email and phone number in the contact details for the home team, or the notes for a tournament
        $start_date = gmdate("U");
        if (isset($_GET['start'])) {
            $parsed_date = $this->ParseBBCDateFormat($_GET['start']);
            if (!is_null($parsed_date)) {
                $start_date = $parsed_date;
            }
        }
        
        $matches = $this->ReadMatchData($start_date);
        $team_ids = $this->GetHomeTeamIds($matches);
        $teams_indexed_by_id = $this->ReadTeamData($team_ids);        

        header("Content-type: text/xml");
        
        $feed = new SimpleXMLElement("<feed />");
 
        $matches = $this->FilterMatchesWithMissingData($matches, $teams_indexed_by_id);
        $this->AddPartnerToFeed($feed, $valid_api_key);
        $this->AddLocationsToFeed($feed, $matches);
        $this->AddActivitiesToFeed($feed, $matches, $teams_indexed_by_id, $valid_api_key);

        echo $feed->asXML();
        
        exit();
	}

    private function ParseBBCDateFormat($date) {
    
        if (!preg_match('/^[0-9]{14}$/', $date)) {
            return null;
        }
        
        return mktime(substr($date,8,2), substr($date,10,2), substr($date,12,2), substr($date,4,2), substr($date,6,2), substr($date,0,4));
    }

    private function AddPartnerToFeed(SimpleXMLElement $feed, $valid_api_key){
        
        $partners = $feed->addChild("partners");
        
        $partner = $partners->addChild("partner");
        $partner->addAttribute("external_id", $this->GetSettings()->GetOrganisationLinkedDataUri());
        $partner->addChild("name", "Stoolball England");
        $partner->addChild("organisational_type")->addAttribute("value", 10);
        $partner->addChild("url", "https://www.stoolball.org.uk");
        $partner->addChild("description", "Stoolball England is the governing body of stoolball: a fast, fun, exciting sport. We promote stoolball, set the rules, train coaches and supply equipment.");
        $partner->addChild("address_line1", "");
        $partner->addChild("city", "");
        $partner->addChild("postcode", "");
        if ($valid_api_key) {
            $partner->addChild("contact_name", "");
            $partner->addChild("contact_job_title", "");
            $partner->addChild("contact_email", $this->GetSettings()->GetTechnicalContactEmail());
            $partner->addChild("contact_phone", "");
        }
    }


    private function ReadMatchData($start_date) {
        
        require_once('stoolball/match-manager.class.php');
        $match_manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());
        $match_manager->FilterByDateStart($start_date);
        $match_manager->FilterByMatchType(array(MatchType::CUP, MatchType::FRIENDLY, MatchType::LEAGUE, MatchType::PRACTICE, MatchType::TOURNAMENT));
        $match_manager->ReadByMatchId();
        $data = $match_manager->GetItems();
        unset($match_manager);
        return $data;
    }
    
    private function ReadTeamData(array $team_ids) {
            
        if (!count($team_ids)) return array();
        
        require_once('stoolball/team-manager.class.php');
        $team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->ReadById($team_ids);
        $teams = $team_manager->GetItems();
        unset($team_manager);
        
        $team_ids = array();
        foreach ($teams as $team) {
            $team_ids[] = $team->GetId();
        }
        return array_combine($team_ids, $teams);
    }


    private function GetHomeTeamIds(array $data) {
        
        $team_ids = array();
        foreach ($data as $match) {
            /* @var $match Match */
            if ($match->GetHomeTeamId()) {
                $team_ids[] = $match->GetHomeTeamId();
            }
        }
        
        return array_unique($team_ids);
    }

    private function FilterMatchesWithMissingData(array $matches, array $teams_indexed_by_id) {
            
        foreach ($matches as $key => $match) {
            /* @var $match Match */
            if ($match->GetMatchType() == MatchType::TOURNAMENT) {

                if (!$match->GetContactEmail() or !$match->GetContactPhone()) {
                    unset($matches[$key]);
                }

            } else {

                if (!$match->GetHomeTeamId()) {
                    unset($matches[$key]);
                    continue;
                }
                
                $home_team = $teams_indexed_by_id[$match->GetHomeTeamId()];
                if (is_null($home_team) or !$home_team->GetContactEmail() or !$home_team->GetContactPhone()) {
                    unset($matches[$key]);
                }            
            }           
        }
        
        return $matches;
    }

    private function AddLocationsToFeed(SimpleXMLElement $feed, array $data) {
           
        $locations = $feed->addChild("locations");
        $locations_done = array();

        foreach ($data as $match)
        {
            /* @var $match Match */            
            if (!$match->GetGroundId()) {
                continue;
            }
            
            if (in_array($match->GetGroundId(), $locations_done))
            {
               continue; 
            }
            
            $ground = $match->GetGround();      
            $location = $locations->addChild("location");
            $location->addAttribute("external_id", $ground->GetLinkedDataUri());
            $location->addAttribute("external_partner_id", $this->GetSettings()->GetOrganisationLinkedDataUri());
            $type = $location->addChild("location_type");
            $type->addAttribute("value", "12");
            
            $lines = array();
            $address = $ground->GetAddress();
            if ($address->GetSaon()) $lines[] = $address->GetSaon();
            if ($address->GetPaon()) $lines[] = $address->GetPaon();
            if ($address->GetStreetDescriptor()) $lines[] = $address->GetStreetDescriptor();
            if ($address->GetLocality()) $lines[] = $address->GetLocality();
            
            $count = count($lines);
            if ($count > 0) $location->addChild("name", $lines[0]);
            if ($count > 1) $location->addChild("address_line1", $lines[1]);
            if ($count == 3) $location->addChild("address_line2", $lines[2]);
            if ($count == 4) $location->addChild("address_line2", $lines[2] . ", " . $lines[3]);
            $location->addChild("city", $address->GetTown());
            $location->addChild("postcode", $address->GetPostcode());
            $location->addChild("latitude", $address->GetLatitude());
            $location->addChild("longitude", $address->GetLongitude());
            $location->addChild("url", $ground->GetNavigateUrl());
            
            $locations_done[] = $match->GetGroundId();
        }
    }

    private function AddActivitiesToFeed(SimpleXMLElement $feed, array $matches, array $teams_indexed_by_id, $valid_api_key) {
        
        require_once("search/match-search-adapter.class.php");    
        $dont_just_turn_up = "\n\nIf you'd like to play, contact one of the clubs playing and ask how you can get involved. If you simply turn up on the day you'll be able to watch but " .
            "may not get to play.";
        
        $activities = $feed->addChild("activities");
        foreach ($matches as $match)
        {
            $activity = $activities->addChild("activity");
            
            /* @var $match Match */
            /* @var $activity SimpleXMLElement */
            
            $activity->addAttribute("external_id", $match->GetLinkedDataUri());
            $activity->addAttribute("external_partner_id", $this->GetSettings()->GetOrganisationLinkedDataUri());
            if ($match->GetGroundId()) {
                $activity->addAttribute("external_location_id", $match->GetGround()->GetLinkedDataUri());
            }
            
            $activity->addChild("title", Html::Encode($match->GetTitle()));
            
            $adapter = new MatchSearchAdapter($match);
            $description = $adapter->GetSearchDescription();
            $description = "Stoolball " . strtolower(substr($description, 0, 1)) . substr($description, 1);
            
            $cancelled = (in_array($match->Result()->GetResultType(), array(MatchResult::ABANDONED, MatchResult::CANCELLED, MatchResult::POSTPONED, MatchResult::AWAY_WIN_BY_FORFEIT, MatchResult::HOME_WIN_BY_FORFEIT)));
            if ($cancelled) {
                $description = "CANCELLED. " . $description;
            }
            
            $activity->addChild("short_description", $description);
            
            $activity->addChild("description", $description . $dont_just_turn_up);
            
            $activity->addChild("is_free", 0);
            $activity->addChild("requires_registration", 1);
            
            if ($match->GetMatchType() == MatchType::TOURNAMENT)
            {
                $activity->addChild("cost_information", "Most stoolball tournaments cost £1 per player for the whole day.");
            } else {
                $activity->addChild("cost_information", "Most teams charge a small annual and/or match fee.");
            }
            
            $activity->addChild("url", "https://" . $this->GetSettings()->GetDomain() . $match->GetNavigateUrl());
            
            $occurrences = $activity->addChild("occurrences");
            $times = $occurrences->addChild("times");
            $times->addChild("start_time", date('c', $match->GetStartTime()));
            if ($match->GetMatchType() == MatchType::TOURNAMENT) {
                $approx_duration_hours = 6;
            } else {
                $approx_duration_hours = 2;
            }            
            $times->addChild("finish_time", date('c', $match->GetStartTime()+(60*60*$approx_duration_hours)));
            
            $times->addChild("cancelled", $cancelled ? "1" : "0");

            $category = $activity->addChild("category");
            $category->addAttribute("value", "8");
            
            $suitable = $activity->addChild("suitable_for");
            $suitable->addChild("any_age", 0);
            $suitable->addChild("s0-4", 0);
            $suitable->addChild("s5-6", 0);
            $suitable->addChild("s7-10", 0);
            $suitable->addChild("s11-13", 1);
            $suitable->addChild("s14-15", 1);
            $suitable->addChild("s16-17", 1);
            $suitable->addChild("s18", 1);
            
            $activity->addChild("family_friendly", 1);
            $activity->addChild("activity_type", "EVENT");
            
            if ($valid_api_key) {
                $contact = $activity->addChild("contact");
                if ($match->GetMatchType() == MatchType::TOURNAMENT)
                {
                  $contact->addChild("contact_email", $match->GetContactEmail());
                  $contact->addChild("contact_phone", $match->GetContactPhone());
                } else {
                    $home_team = $teams_indexed_by_id[$match->GetHomeTeamId()];
                    /* @var $home_team Team */
                  $contact->addChild("contact_email", $home_team->GetContactEmail());
                  $contact->addChild("contact_phone", $home_team->GetContactPhone());
                }
            }
            
            $photo = $activity->addChild("photo");
            $photo->addChild("photo_url", "https://www.stoolball.org.uk/images/bbc-getinspired-feed/photo" . substr($match->GetId(), strlen($match->GetId())-1) . ".jpg");
            $photo->addChild("photo_description", "Photo of a stoolball match in progress");
        }
    } 
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
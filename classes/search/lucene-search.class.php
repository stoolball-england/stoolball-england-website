<?php
class LuceneSearch
{
    private $index;

    /**
     * Create a LuceneSearch instance
     */
    public function __construct()
    {
        require_once ($_SERVER["DOCUMENT_ROOT"] . "/../Zend/Search/Lucene.php");
        require_once ($_SERVER["DOCUMENT_ROOT"] . "/../StandardAnalyzer/Analyzer/Standard/English.php");
       # Zend_Search_Lucene_Analysis_Analyzer::setDefault(new StandardAnalyzer_Analyzer_Standard_English());
    }

    /**
     * Get a reference to the Lucene index
     */
    public function GetIndex()
    {
/*        if (!isset($this->index))
        {
            $this->index = Zend_Search_Lucene::open($_SERVER["DOCUMENT_ROOT"] . "/../lucene/index");
        }
        return $this->index;*/
    }

    /**
     * Save any changes made to the search index
     */
    public function CommitChanges()
    {
        /*$this->GetIndex()->commit();*/
    }

    /**
     * Delete document matching the specified id from the index
     */
    public function DeleteDocumentById($id)
    {
        /*$index = $this->GetIndex();

        $results = $index->find("urn:$id");
        foreach ($results as $result)
        {
            $index->delete($result->id);
        }*/
    }

    /**
     * Delete all documents matching the specified type from the index
     */
    public function DeleteDocumentsByType($type)
    {
        /*$index = $this->GetIndex();

        $results = $index->find("type:$type");
        foreach ($results as $result)
        {
            $index->delete($result->id);
        }*/
    }

    /**
     * Create a new document in the index
     * @param $type string
     * @param $id string
     * @param $url string
     * @param $title string
     * @param $description string
     * @param $content string
     */
    public function CreateStandardDocument($type, $id, $url, $title, $description, $content = "",$keywords = "")
    {
        $doc = new Zend_Search_Lucene_Document();
        $doc->addField(Zend_Search_Lucene_Field::Text('type', $type, 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::Text('urn', $id, 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnIndexed('url', $url, 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::Text('title', $this->RemoveUnsupportedCharacters($title), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::Text('description', $this->RemoveUnsupportedCharacters($description), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnStored('content', $this->RemoveUnsupportedCharacters($content), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnStored('keywords', $this->RemoveUnsupportedCharacters($keywords), 'utf-8'));
        return $doc;
    }

    /**
     * Try to remove characters which are not in the ASCII character set, because Lucene uses ASCII
     * @param $text string
     */
    private function RemoveUnsupportedCharacters($text) 
    {
        # This method successfully removes Finnish characters, but leaves other characters which fail
        # when Lucene runs them through iconv()
        # $text = mb_convert_encoding($text, "ISO-8859-1", "UTF-8");
        
        # This also removes Finnish characters, but causes others still to fail
        # $text = htmlentities($text, ENT_NOQUOTES, "ISO-8859-1", false);
        # $text = preg_replace('/&.*?;/', "", $text);
        
        # This works for everything except Finnish characters which are not in the ASCII character set.
        # Unfortunately combining multiple methods doesn't work, because this throws an error if it fails, 
        # and if you run other methods first to head off problems before this fails, then this stops working
        # and you get errors when Lucene uses this method. The IGNORE flag should suppress that error, but
        # doesn't work on the live site even though it works perfectly in development.
        # 
        # Unfortunately, because Finnish characters are so rare on this website, best option is to accept that
        # they will throw an error and the text will be truncated at the point where the character occurs.
        $text = iconv("UTF-8", 'ASCII//TRANSLIT//IGNORE', $text);
        return $text;
    }

    /**
     * Add a stoolball team to the index without committing the change
     * @param $team Team
     */
    public function IndexTeam(Team $team)
    {
        $content = array($team->GetGround()->GetAddress()->GetAdministrativeArea(), $team->GetIntro(), $team->GetPlayingTimes(), $team->GetCost(), $team->GetContact());
        $keywords = array($team->GetName(), $team->GetName(), $team->GetName(), $team->GetName(), $team->GetGround()->GetAddress()->GetLocality(), $team->GetGround()->GetAddress()->GetTown());

        $doc = $this->CreateStandardDocument("team", "team" . $team->GetId(), $team->GetNavigateUrl(), $team->GetNameAndType(), $team->GetSearchDescription(), implode(" ", $keywords), implode(" ", $content));
        //$this->GetIndex()->addDocument($doc);
    }

    /**
     * Add a stoolball ground to the index without committing the change
     * @param $ground  Ground
     * @param $team_manager TeamManager
     */
    public function IndexGround(Ground $ground, TeamManager $team_manager)
    {
        # Get teams based at the ground
        $team_manager->FilterByGround(array($ground->GetId()));
        $team_manager->FilterByActive(true);
        $team_manager->ReadTeamSummaries();
        $teams = $team_manager->GetItems();

        $description = $ground->GetName();
        $teams_count = count($teams);
        if ($teams_count)
        {
            $description .= " is home to ";
            for ($i = 0; $i < $teams_count; $i++)
            {
                $description .= $teams[$i]->GetName();
                if ($i < ($teams_count - 2))
                {
                    $description .= ", ";
                }
                if ($i == ($teams_count - 2))
                {
                    $description .= " and ";
                }
            }
            $description .= ".";
        }
        else
        {
            $description .= " is not currently home to any teams.";
        }

        $keywords = array($ground->GetAddress()->GetLocality(), $ground->GetAddress()->GetTown());
        $content = array($ground->GetAddress()->GetStreetDescriptor(), $ground->GetAddress()->GetAdministrativeArea(), $ground->GetDirections(), $ground->GetParking(), $ground->GetFacilities());

        $doc = $this->CreateStandardDocument("ground", "ground" . $ground->GetId(), $ground->GetNavigateUrl(false), $ground->GetNameAndTown(), $description, implode(" ", $keywords), implode(" ", $content));
        # $this->GetIndex()->addDocument($doc);

    }

    /**
     * Add a stoolball player to the index without committing the changes
     * @param $player Player
     */
    public function IndexPlayer(Player $player)
    {
        if ($player->GetPlayerRole() == Player::PLAYER)
        {
            $name = $player->GetName() . ", " . $player->Team()->GetName();
        }
        else
        {
            $name = $player->GetName() . " conceded by " . $player->Team()->GetName();
        }

        $doc = $this->CreateStandardDocument("player", "player" . $player->GetId(), $player->GetPlayerUrl(), $name, $player->GetPlayerDescription());
        # $this->GetIndex()->addDocument($doc);
    }

    /**
     * Add a stoolball competition to the index without committing the changes
     * @param $competition Competition
     */
    public function IndexCompetition(Competition $competition)
    {
        $season = $competition->GetLatestSeason();
        $teams = $season->GetTeams();
        $keywords = array();
        $content = array();
        $keywords[] = $competition->GetName();
        $keywords[] = $competition->GetName();
        $keywords[] = $competition->GetName();
        foreach ($teams as $team)
        {
            $keywords[] = $team->GetName();
            $keywords[] = $team->GetName();
            $keywords[] = $team->GetGround()->GetAddress()->GetLocality();
            $keywords[] = $team->GetGround()->GetAddress()->GetTown();
        }
        $content[] = $competition->GetIntro();
        $content[] = $competition->GetContact();

        $doc = $this->CreateStandardDocument("competition", "competition" . $competition->GetId(), $competition->GetNavigateUrl(), $competition->GetName(), $competition->GetSearchDescription(), implode(" ", $keywords), implode(" ", $content));
        # $this->GetIndex()->addDocument($doc);

    }

    /**
     * Add a stoolball match to the index without committing the changes
     * @param $match Match
     */
    public function IndexMatch(Match $match)
    {
        $doc = $this->CreateStandardDocument("match", "match" . $match->GetId(), $match->GetNavigateUrl(), $match->GetTitle() . ", " . $match->GetStartTimeFormatted(true, false), $match->GetSearchDescription(), "", $match->GetNotes());
        # $this->GetIndex()->addDocument($doc);
    }

    /**
     * Add a WordPress post to search results
     * @param $post_id int
     * @param $url string
     * @param $title string
     * @param $content string
     */
    public function IndexWordPressPost($post_id, $url, $title, $content)
    {
        $description = $content;
        $description = strip_tags(trim($description));
        $break = strpos($description, "\n");
        if ($break !== false and $break > 0)
        {
            $description = substr($description, 0, $break - 1);
        }

        $doc = $this->CreateStandardDocument("post", "post" . $post_id, $url, $title, $description, "", $content);
        # $this->GetIndex()->addDocument($doc);
    }

    /**
     * Add a WordPress page to search results
     * @param $page_id int
     * @param $url string
     * @param $title string
     * @param $description string
     * @param $content string
     */
    public function IndexWordPressPage($page_id, $url, $title, $description, $content)
    {
        $doc = $this->CreateStandardDocument("page", "page" . $page_id, $url, $title, $description, "", $content);
        # $this->GetIndex()->addDocument($doc);
    }

}
?>
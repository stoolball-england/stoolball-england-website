<?php
require_once ("search-adapter.interface.php");
require_once ("search-item.class.php");

/**
 * Builds details from a ground into an entry for the search index
 */
class GroundSearchAdapter implements ISearchAdapter
{
	private $searchable;
    private $ground;

	public function __construct(Ground $ground)
	{
        $this->ground = $ground;
        
		$this->searchable = new SearchItem("ground", "ground" . $ground->GetId(), $ground->GetNavigateUrl(false), $ground->GetNameAndTown());
		$this->searchable->Description($this->GetSearchDescription());

		$keywords = array($ground->GetAddress()->GetLocality(), $ground->GetAddress()->GetTown());
		$this->searchable->Keywords(implode(" ", $keywords));

		$content = array($ground->GetAddress()->GetStreetDescriptor(), $ground->GetAddress()->GetAdministrativeArea(), $ground->GetDirections(), $ground->GetParking(), $ground->GetFacilities());
		$this->searchable->FullText(implode(" ", $content));

		$this->searchable->RelatedLinksHtml('<ul>' . 
		                                    '<li><a href="' . $ground->GetStatsNavigateUrl() . '">Statistics</a></li>' . 
		                                    '</ul>');

	}

	/**
	 * Gets text to use as the description of this team in search results
	 */
	public function GetSearchDescription()
	{
        $description = $this->ground->GetName();
        $teams = $this->ground->Teams()->GetItems();
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
        return $description;
    }

	/**
	 * Gets a searchable item representing the ground in the search index
	 * @return SearchableItem
	 */
	public function GetSearchableItem()
	{
		return $this->searchable;
	}

}

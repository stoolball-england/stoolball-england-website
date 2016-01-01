<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a player into an entry for the search index
 */
class PlayerSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Player $player) {

        $this->searchable = new SearchItem("player", "player" . $player->GetId(), $player->GetPlayerUrl());
        $this->searchable->Description($player->GetPlayerDescription());
        $this->searchable->WeightOfType(20);

        if ($player->GetPlayerRole() == Player::PLAYER)
        {
            $this->searchable->Title($player->GetName() . ", " . $player->Team()->GetName());
            $this->searchable->WeightWithinType($player->GetTotalMatches());
        }
        else
        {
            $this->searchable->Title($player->GetName() . " conceded by " . $player->Team()->GetName());
        }
        $this->searchable->Keywords($this->searchable->Title());
    }
    
    /**
     * Gets a searchable item representing the player in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}

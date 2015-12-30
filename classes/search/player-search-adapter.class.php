<?php
require_once("search-adapter.interface.php");
require_once("search-item.class.php");

/**
 * Builds details from a player into an entry for the search index
 */
class PlayerSearchAdapter implements ISearchAdapter {
        
    private $searchable;
    
    public function __construct(Player $player) {

       if ($player->GetPlayerRole() == Player::PLAYER)
        {
            $name = $player->GetName() . ", " . $player->Team()->GetName();
        }
        else
        {
            $name = $player->GetName() . " conceded by " . $player->Team()->GetName();
        }
        
        $this->searchable = new SearchItem("player", "player" . $player->GetId(), $player->GetPlayerUrl(), $name, $player->GetPlayerDescription());
    }
    
    /**
     * Gets a searchable item representing the player in the search index
     * @return SearchableItem
     */
    public function GetSearchableItem() {
        return $this->searchable;
    }
}

<?php
require_once("stoolball/clubs/club.class.php");
require_once("stoolball/ground.class.php");

/**
 * A school is a special kind of club
 */
class School extends Club {
    private $ground;
    
    /**
     * Creates a new school
     */
    public function __construct(SiteSettings $settings) {
        parent::__construct($settings);
        $this->SetTypeOfClub(Club::SCHOOL);
        $this->ground = new Ground($settings);
    }
    
    /**
     * Gets the sports ground at the school
     */
    public function Ground() {
        return $this->ground;
    }
    
    /**
     * Gets the URL for editing contact information for the school
     */
    public function EditContactsUrl() {
        return $this->GetNavigateUrl() . "/edit-contacts";
    }
}
?>

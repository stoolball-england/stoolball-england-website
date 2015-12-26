<?php 
class MatchQualification
{
    /**
     * It's not recorded how teams qualify for a match or tournament
     */
    const UNKNOWN = 0;

    /**
     * A tournament which any team of the right player type may enter
     */
    const OPEN_TOURNAMENT = 1;
    
    /**
     * An invite-only tournament or one for which a team must qualify
     */
    const CLOSED_TOURNAMENT = 2;  
}
?>
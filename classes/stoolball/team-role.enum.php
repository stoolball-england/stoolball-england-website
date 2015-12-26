<?php
class TeamRole
{
	/**
	* @returns int
	* @desc The team is responsible for hosting the game
	*/
	public static function Home() { return 1; }
	
	/**	
	* @returns int
	* @desc The team is not responsible for hosting the game
	*/
	public static function Away() { return 2; }
}
?>
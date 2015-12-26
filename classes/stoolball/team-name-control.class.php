<?php
require_once("xhtml/xhtml-element.class.php");
require_once('stoolball/team.class.php');

/**
 * Displays team name and type using schema.org metadata in RDFa
 * @author Rick
 *
 */
class TeamNameControl extends XhtmlElement
{
	/**
	 * Create new TeamNameControl
	 * @param Team $team
	 * @param string $container_element
	 */
	public function __construct(Team $team, $container_element)
	{
		parent::XhtmlElement($container_element);

		$name = $team->GetName();
		$type = is_null($team->GetPlayerType()) ? '' : PlayerType::Text($team->GetPlayerType());
        if ($type and strpos(strtolower(str_replace("'", '', $name)), strtolower(str_replace("'", '', $type))) !== false)
        {
            $type = "";
        } 
        $town = (is_null($team->GetGround()) or is_null($team->GetGround()->GetAddress())) ? "" : $team->GetGround()->GetAddress()->GetTown();
        if ($town and strpos(strtolower($name), strtolower($town)) !== false)
        {
            $town = "";
        } 
        
		if ($type or $town)
		{
		    $html = '<span property="schema:name">' . htmlentities($name, ENT_QUOTES, "UTF-8", false) . '</span>';
            if ($town) 
            {
                $html .= htmlentities(", $town", ENT_QUOTES, "UTF-8", false);
            }
            if ($type)
            {
                $html  .= htmlentities(" ($type)", ENT_QUOTES, "UTF-8", false);
            }
			$this->AddControl($html);
		}
		else
		{
			$this->AddAttribute("property", "schema:name");
			$this->AddControl(htmlentities($name, ENT_QUOTES, "UTF-8", false));
		}
	}
}
?>
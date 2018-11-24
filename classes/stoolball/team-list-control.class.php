<?php
require_once('xhtml/xhtml-element.class.php');
require_once("stoolball/team-name-control.class.php");

class TeamListControl extends Placeholder
{
	private $teams;
	private $s_no_teams_message;
	private $b_show_type = false;
	private $is_navigation_list;

	function __construct($a_teams=null)
	{
		parent::__construct();
		$this->teams = (is_array($a_teams)) ? $a_teams : array();
	}

	/**
	 * @return void
	 * @param bool $b_show
	 * @desc Sets whether to show the player type after the team name
	 */
	function SetShowPlayerType($b_show)
	{
		$this->b_show_type = (bool)$b_show;
	}

	/**
	 * @return bool
	 * @desc Gets whether to show the player type after the team name
	 */
	function GetShowPlayerType()
	{
		return $this->b_show_type;
	}

	/**
	 * @return void
	 * @param string $s_message
	 * @desc Sets the message to display if there are no teams
	 */
	function SetNoTeamsMessage($s_message)
	{
		$this->s_no_teams_message = (string)$s_message;
	}

	/**
	 * @return string $s_message
	 * @desc Gets the message to display if there are no teams
	 */
	function GetNoTeamsMessage()
	{
		return $this->s_no_teams_message;
	}

	/**
	 * Sets whether this list is used as primary navigation
	 * @param bool $is_list
	 */
	public function SetIsNavigationList($is_list)
	{
		$this->is_navigation_list = (bool)$is_list;
	}

	/**
	 * Gets whether this list is used as primary navigation
	 */
	public function GetIsNavigationList()
	{
		return $this->is_navigation_list;
	}

	function OnPreRender()
	{
		/* @var $o_team Team */

		$a_teams = $this->teams;

		if (count($a_teams))
		{
			$o_list = new XhtmlElement('ul');
			if ($this->is_navigation_list)
			{
				$o_list->SetCssClass('teamList nav');
				$nav = new XhtmlElement("nav", $o_list);
				$this->AddControl($nav);
			}
			else
			{
				$o_list->SetCssClass('teamList');
				$this->AddControl($o_list);
			}

			$i_count = 0;
			foreach ($a_teams as $o_team)
			{
				if (strtolower(get_class($o_team)) == 'team')
				{
					$li = new XhtmlElement('li');
					$li->AddAttribute("typeof", "schema:SportsTeam");
					$li->AddAttribute("about", $o_team->GetLinkedDataUri());

					if ($this->b_show_type)
					{
						$link = new TeamNameControl($o_team, "a");
					}
					else
					{
						$link = new XhtmlElement('a', Html::Encode($o_team->GetName()));
						$link->AddAttribute("property", "schema:name");
					}
					$link->AddAttribute('href', $o_team->GetNavigateUrl());
					$link->AddAttribute("rel", "schema:url");
					$li->AddControl($link);

					# Add not playing, if relevant
					if (!$o_team->GetIsActive())
					{
						$li->AddControl(' (doesn\'t play any more)');
					}

					$o_list->AddControl($li);
					$i_count++;
				}
			}
		}
		else
		{
			# Display no teams message
			if ($this->s_no_teams_message) $this->AddControl(new XhtmlElement('p', $this->s_no_teams_message));
		}
	}
}
?>
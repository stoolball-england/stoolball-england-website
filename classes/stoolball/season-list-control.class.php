<?php
require_once('xhtml/xhtml-element.class.php');
require_once('stoolball/season.class.php');

class SeasonListControl extends XhtmlElement
{
	var $a_seasons;
	var $a_excluded;
	private $show_competition = false;
	private $url_method = 'GetNavigateUrl';

	function SeasonListControl($a_seasons=null)
	{
		parent::XhtmlElement("div");
		$this->SetCssClass("season-list");
		$this->a_seasons = (is_array($a_seasons)) ? $a_seasons : array();
		$this->a_excluded = array();
	}

	/**
	 * @return void
	 * @param bool $show_competition
	 * @desc Sets whether to include the competition name in season links
	 */
	public function SetShowCompetition($show_competition)
	{
		$this->show_competition = (bool)$show_competition;
	}

	/**
	 * @return bool
	 * @desc Gets whether to include the competition name in season links
	 */
	public function GetShowCompetition()
	{
		return $this->show_competition;
	}

	/**
	 * Sets the method of the Season class used to get the URL to link to
	 * @param string $method_name
	 */
	public function SetUrlMethod($method_name)
	{
		$this->url_method = (string)$method_name;
	}

	/**
	 * Gets the method of the Season class used to get the URL to link to
	 * @return string
	 */
	public function GetUrlMethod() { return $this->url_method; }

	/**
	 * @return void
	 * @param Season[] $a_excluded
	 * @desc Sets the seasons which should not be listed
	 */
	function SetExcludedSeasons($a_excluded)
	{
		if (is_array($a_excluded)) $this->a_excluded = $a_excluded;
	}

	/**
	 * @return Season[]
	 * @desc Sets the seasons which should not be listed
	 */
	function GetExcludedSeasons()
	{
		return $this->a_excluded;
	}

	function OnPreRender()
	{
		/* @var $o_season Season */
		/* @var $o_excluded Season */

		$current_competition = null;
		$single_list_open = false;
		foreach ($this->a_seasons as $o_season)
		{
			if ($o_season instanceof Season)
			{
				$b_excluded = false;
				foreach ($this->a_excluded as $o_excluded)
				{
					if ($o_excluded->GetId() == $o_season->GetId())
					{
						$b_excluded = true;
						break;
					}
				}

				if (!$b_excluded)
				{
					if (method_exists($o_season, $this->url_method))
					{
						$url_method = $this->url_method;
						$url = $o_season->$url_method();
					}
					else
					{
						$url = $o_season->GetNavigateUrl();
					}

					if (!$this->show_competition and !$single_list_open)
					{
						$this->AddControl('<ul>');
						$single_list_open = true;
					}
					else if ($this->show_competition and $current_competition != $o_season->GetCompetition()->GetId())
					{
						if ($current_competition != null) $this->AddControl("</ul>");
						$this->AddControl("<h2>" . htmlentities($o_season->GetCompetition()->GetName(), ENT_QUOTES, "UTF-8", false) . '</h2><ul>');
						$current_competition = $o_season->GetCompetition()->GetId();
					}
					$o_link = new XhtmlElement('a', htmlentities($o_season->GetName(), ENT_QUOTES, "UTF-8", false) . " season");
					$o_link->AddAttribute('href', $url);
					$o_li = new XhtmlElement('li');
					$o_li->AddControl($o_link);

					$this->AddControl($o_li);
				}
			}
		}
		if ($current_competition != null  or $single_list_open) $this->AddControl("</ul>");
	}
}
?>
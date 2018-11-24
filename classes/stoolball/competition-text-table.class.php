<?php
require_once('xhtml/tables/xhtml-table.class.php');
require_once('xhtml/xhtml-anchor.class.php');
require_once('person/postal-address-control.class.php');

class CompetitionTextTable extends XhtmlTable
{
	/**
	 * The competition to display
	 *
	 * @var Competition
	 */
	private $o_competition;

	function __construct(Competition $o_competition)
	{
		parent::__construct();
		$this->o_competition = $o_competition;
	}

	function OnPreRender()
	{
		/* @var $o_season Season */
		/* @var $o_team Team */

		$this->SetCaption($this->o_competition->GetName());
		$o_headings = new XhtmlRow(array('Name of team', 'Ground address', 'Contact details'));
		$o_headings->SetIsHeader(true);
		$this->AddRow($o_headings);

		foreach ($this->o_competition->GetLatestSeason()->GetTeams() as $o_team)
		{
			if ($o_team instanceof Team)
			{
				$o_row = new XhtmlRow();
				$o_row->AddCell(new XhtmlAnchor(Html::Encode($o_team->GetName()), $o_team->GetEditTeamUrl()));
				$o_row->AddCell(new PostalAddressControl($o_team->GetGround()->GetAddress()));
				$private = $o_team->GetPrivateContact();
				$public = $o_team->GetContact();
				$private_header = '<h2 style="margin:0; font-size:1.1em; color: #900;">Private</h2>';
				if ($private and $public)
				{
					$contact = $public . $private_header . $private;
				}
				else
				{
					$contact = $private ? $private_header . $private : $public;
				}
				$o_row->AddCell(nl2br(XhtmlMarkup::ApplySimpleTags(XhtmlMarkup::ApplyLinks($contact, true))));
				$this->AddRow($o_row);
			}
		}

		parent::OnPreRender();
	}
}
?>
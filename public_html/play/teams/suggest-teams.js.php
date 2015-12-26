<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('context/stoolball-settings.class.php');
require_once('page/page.class.php');

class CurrentPage extends Page
{
    public function OnPageInit()
	{
		# This is a JavaScript file
		if (!headers_sent()) header("Content-Type: text/javascript; charset=utf-8");
	}

	public function OnLoadPageData()
	{
		require_once("stoolball/team-manager.class.php");
		$team_manager = new TeamManager($this->GetSettings(), $this->GetDataConnection());
        $team_manager->FilterByActive(true);
        if (isset($_GET['except']))
        {
            $except = explode(",", $_GET['except']);
            
            # Make sure all values are numeric. If not, ignore them all.
            foreach ($except as $id) 
            {
                if (!is_numeric($id))
                {
                    $except = array();
                    break;
                } 
            }
            $team_manager->FilterExceptTeams($except);
        } 
		$team_manager->ReadTeamSummaries();
		$teams = $team_manager->GetItems();
		unset($team_manager);

		if (count($teams))
		{
            # Build up the data to display in the autocomplete dropdown
			$suggestions = array();
    		foreach ($teams as $team)
			{
				/* @var $team Team */
				$escaped_name = str_replace("'", "\'",$team->GetNameAndType()); # escape single quotes because this will become JS string

				$suggestions[] = "{label:\"" . $escaped_name . "\"}"; # escape single quotes for JS string
			}

			# Write those names as a JS array
			?>
$(function()
{
	var teams = [<?php echo implode(",\n",$suggestions);?>];

	// find anywhere a team's name should be entered
	$("input.team", "form").each(enableSuggestions);

	function enableSuggestions()
	{
		// hook up the autocomplete to work with just the team's name, not their supporting info
		var input = $(this);
		if (!input.hasClass("autocomplete"))
		{
			input.autocomplete({source:teams}).data( "autocomplete" )._renderItem = function( ul, item ) {
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append("<a>" + item.label + "</a>")
					.appendTo( ul )};
		}
		else
		{
			input.autocomplete("option","source",teams);
		}
	}
});
			<?php
		}
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::EDIT_MATCH, false);
?>
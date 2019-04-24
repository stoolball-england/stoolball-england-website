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
		require_once("stoolball/schools/school-manager.class.php");
		$school_manager = new SchoolManager($this->GetSettings(), $this->GetDataConnection());
        
        $search_terms = array();
        if (isset($_GET["name"])) $search_terms[] = $_GET["name"]; 
        if (isset($_GET["town"])) $search_terms[] = $_GET["town"]; 
        if (count($search_terms)) {
            $school_manager->FilterBySearch(implode(' ', $search_terms));
        }
        
		$school_manager->ReadById();
		$schools = $school_manager->GetItems();
		unset($school_manager);

		if (count($schools))
		{
            # Build up the data to display in the autocomplete dropdown
			$school_data = array();
    		foreach ($schools as $school)
			{
				/* @var $school Club */
				# escape single quotes because this will become JS string
				$escaped_name = str_replace("'", "\'",$school->GetName()); 
                $escaped_url = str_replace("'", "\'",$school->GetNavigateUrl()); 

				$school_data[] = '{"name":"' . $escaped_name . '","url":"' . $escaped_url . '"}';
			}

			# Write those names as a JS array
			?>
[<?php echo implode(",\n",$school_data);?>]
			<?php
		}
		exit();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
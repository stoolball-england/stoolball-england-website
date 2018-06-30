<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');
set_time_limit(0);

require_once('page/stoolball-page.class.php');
require_once('stoolball/player.class.php');

class CurrentPage extends StoolballPage
{
    private $process;

    public function OnPostback()
    {
        $formats = $this->GetSettings()->GetShortUrlFormats();
        foreach($formats as $format) {
                /* @var $format ShortUrlFormat */
            $key = substr($format->GetTable(),4);
            if (isset($_POST[$key]))  {
                $this->RegenerateFormat($format, $key);
                return ;
            }             
        }                
    }

    private function RegenerateFormat(ShortUrlFormat $format, $process_id)
    {
        # KNOWN ISSUE: Only uses GetShortUrlForType which for a match means tournaments are wrong

        require_once("data/process-manager.class.php");
        $this->process = new ProcessManager($process_id, 50);

        if ($this->process->ReadyToDeleteAll())
        {
            $this->GetDataConnection()->query('DELETE FROM nsa_short_url WHERE short_url_base IN (
                                                    SELECT ' . $format->GetShortUrlField() . '
                                                    FROM ' . $format->GetTable() . ')');
        }

        require_once('http/short-url-manager.class.php');
        $short_url_manager = new ShortUrlManager($this->GetSettings(), $this->GetDataConnection());
        $short_url_manager->RegenerateCache();

        # Use format info to get existing short URL and querystring data for each item from the data tables.
        $short_url_manager->ReadAllByFormat($format, $this->process->GetQueryLimit());
        $a_short_urls = $short_url_manager->GetItems();

        # For each short URL runs Save() to re-save records in short url table. Doesn't
        # recalculate whether URLs clash, just takes what was in the data table.
        foreach ($a_short_urls as $o_short_url)
        {
            $short_url_manager->Save($o_short_url);
            $this->process->OneMoreDone();
        }

        unset($short_url_manager);
    }

    function OnPrePageLoad()
    {
        $this->SetPageTitle("Regenerate URL cache");
    }

    function OnPageLoad()
    {
        echo new XhtmlElement('h1', $this->GetPageTitle());

        if (isset($this->process))
        {
            $this->process->ShowProgress();
        }

?>
<form method="POST">
    <div>
<?php 
        $formats = $this->GetSettings()->GetShortUrlFormats();
        foreach($formats as $format) {
                /* @var $format ShortUrlFormat */
            echo '<input type="submit" name="' . substr($format->GetTable(),4) . '" value="Regenerate ' . substr($format->GetTable(),4) . ' URLs" /><br />';            
        }                
        ?>
    </div>
</form>
<?php
}
}

new CurrentPage(new StoolballSettings(), PermissionType::MANAGE_URLS, false);
?>
<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    function OnPrePageLoad()
	{
	    $this->SetPageTitle('Create a poster');
        ?>
        <link href='https://fonts.googleapis.com/css?family=Alegreya+Sans:800,500' rel='stylesheet' type='text/css' />
        <script src="//use.edgefonts.net/league-gothic.js"></script>
        <?php
        $this->LoadClientScript('https://cdnjs.cloudflare.com/ajax/libs/FitText.js/1.2.0/jquery.fittext.min.js');
        $this->LoadClientScript("/play/posters/preview.js");
    }

	function OnPageLoad()
	{
?>
        <h1>Create a poster</h1>
        <p>Create a professional poster to promote your league, club or tournament. Simply fill in your details and click 'Download poster'.</p>
        
        <form method="post" action="./download/" class="poster">
            <input type="hidden" name="design" id="design" value="connie" />
            
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="Play stoolball" />
            
            <label for="slogan">Slogan</label>
            <input type="text" name="slogan" id="slogan" value="Fast, fun family sport" />
            
            <label for="name">League, club or tournament</label>
            <input type="text" name="name" id="name" value="Anytown Stoolball Club" />
            
            <label for="details">Details <span class="hint">(When, where and who to contact)</span></label>
            <textarea name="details" id="details">
Tuesdays from May to August
Anytown Recreation Ground

Call Jo Bloggs on 01234 567890 or email jo.bloggs@example.org
            </textarea>
            
            <div class="buttonGroup">
            <input type="submit" value="Download poster" class="primary" id="download" />
            </div>
        </form>
        
        
        
			<?php
			$this->ShowSocialAccounts();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
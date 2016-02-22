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
        <div id="poster-preview">
        <img src="./designs/connie-preview.jpg" alt="Poster preview: A female player in blue and yellow celebrates a catch" width="100%" />
        <p id="preview-title" role="presentation"></p>
        <p id="preview-slogan" role="presentation"></p>
        <p id="preview-name" role="presentation"></p>
        <p id="preview-details" role="presentation"></p>
        </div>
        
        <form method="post" action="./download/" class="poster">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="Play stoolball" maxlength="18" />
            
            <label for="slogan">Slogan</label>
            <input type="text" name="slogan" id="slogan" value="Fast, fun family sport" maxlength="27" />
            
            <label for="name">League, club or tournament</label>
            <input type="text" name="name" id="name" value="Anytown Stoolball Club" maxlength="40" />
            
            <label for="details">Details <span class="hint">(When, where and who to contact)</span></label>
            <textarea name="details" id="details" maxlength="300">
Tuesdays from May to August
Anytown Recreation Ground

Call Jo Bloggs on 01234 567890 or email jo.bloggs@example.org
            </textarea>
            
            <div class="buttonGroup">
            <input type="submit" value="Download poster" class="primary" />
            </div>
        </form>
        
        
        
			<?php
			$this->ShowSocialAccounts();
	}
}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>
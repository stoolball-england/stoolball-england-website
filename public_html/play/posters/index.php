<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');

class CurrentPage extends StoolballPage
{
    function OnPrePageLoad()
	{
	    $this->SetPageTitle('Create a poster');
        ?>
         <link href='https://fonts.googleapis.com/css?family=Oswald|Alegreya+Sans:800,500' rel='stylesheet' type='text/css'>
         <style>
            #preview { border: 1px solid #555; float: left; margin: 1em 0 ; position:relative; }
            #preview-title { position: absolute; top: 25px; left: 30px; width: 340px; font: normal 55px 'Oswald'; color: #fff;margin: 0; }
            #preview-teaser { position: absolute; top: 270px; left: 230px; font: normal 40px/1.2em 'Oswald'; color: #fff; width: 140px; margin: 0; }
            #preview-name { position: absolute; top: 460px; left: 30px; width: 340px; font: 800 20px 'Alegreya Sans'; color: #fff; margin: 0; }
            #preview-details { position: absolute; top: 485px; left: 30px; width: 340px; font: 500 12px 'Alegreya Sans'; color: #fff;  margin: 0; }
            
            form.poster { width: 250px; float: left; padding: 0 30px; }
            form.poster label { font-weight: bold; margin: 1em 0 .5em; }
            form.poster input[type='text'], form.poster textarea { border-radius: 5px; padding: 5px; border: 1px solid #ccc; font-size: 1.2em; }
            form.poster #teaser { height: 4em; min-height: 0;} 
            form.poster #details { height: 10em; min-height: 0;}
            form.poster .buttonGroup { margin-top: 0; } 
            
        </style>
        <?php
        $this->LoadClientScript("/play/posters/preview.js");
    }

	function OnPageLoad()
	{
?>
        <h1>Create a poster</h1>
        <p>Create a professional poster to promote your league, club or tournament. Simply fill in your details and click 'Download poster'.</p>
        <div id="preview">
        <img src="connie-preview.jpg" width="400" alt="Poster preview: A female player in blue and yellow celebrates a catch" />
        <p id="preview-title" role="presentation"></p>
        <p id="preview-teaser" role="presentation"></p>
        <p id="preview-name" role="presentation"></p>
        <p id="preview-details" role="presentation"></p>
        </div>
        
        <form method="post" action="poster-pdf.php" class="poster">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="Play stoolball" />
            
            <label for="teaser">Teaser text</label>
            <textarea name="teaser" id="teaser">Fast, fun family sport</textarea>
            
            <label for="name">League, club or tournament</label>
            <input type="text" name="name" id="name" value="Anytown Stoolball Club" />
            
            <label for="details">Details<br />(When, where and who to contact)</label>
            <textarea name="details" id="details">
Tuesdays and Thursdays from May to August
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
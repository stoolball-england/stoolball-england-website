if (typeof (jQuery) != "undefined") {
    if (!Modernizr.mq("only screen and (min-width: 500px)")) {
		// If no media query support, copy link tags which use media queries to new ones which don't.'
        $("link[media]", "head").each(function() {
            var a = document.createElement("link");
            a.rel = this.rel;
            a.type = this.type;
            a.href = this.href;
            a.media = "screen";
            a.className = "js" + this.className;
            this.parentNode.insertBefore(a, this);
        });
        
        // Now remove the stylesheets in Internet Explorer conditional comments, which were only there for non-JavaScript users
        $("link.mqIE", "head").attr("disabled", "disabled");
        
        // Polyfill media query in JavaScript
        var mqMedium = $("link.jsmqMedium", "head");
        function applyMedium() {
            mqMedium.each(function() {
                this.disabled = document.documentElement.offsetWidth < 500;
            });
        }
        $(window).resize(applyMedium);
        applyMedium();
    }
    
    if (!Modernizr.mq("only screen and (min-width: 900px)")) {
        
        // Polyfill media query in JavaScript
        var mqLarge = $("link.jsmqLarge", "head");
        function applyLarge() {
            mqLarge.each(function() {
                this.disabled = document.documentElement.offsetWidth < 900;
            });
        }
        $(window).resize(applyLarge);
        applyLarge();
    }

    $(function(){
    	$(".sign-out input[type=submit]").replaceWith('<a href="/you/sign-out" class="screen">Sign out</a>');
    	$(".sign-out a").click(function(e){ e.preventDefault(); $(".sign-out").submit(); });
    });
}

/**
 * Stoolball.org.uk namespace
 */
var Stoolball = {};
Stoolball.DateControl = {
	/**
	 * Help user to complete a blank date, and move focus to next control
	 * 
	 * Note: need to hook up to keydown and keyup to support keyboard in all browsers,
	 * click to support mouse in most browsers, and change to stop click breaking it in
	 * Webkit. Still doesn't work with mouse in Webkit.
	 */
	BlankHourDisablesTime : function(e)
	{
		var isBlank = (this.options[this.selectedIndex].value == '');
		var datePrefix = this.id.substr(0, this.id.length - 4);
		var minuteList = document.getElementById(datePrefix + 'minute');
		var ampmList = document.getElementById(datePrefix + 'ampm');
		if (minuteList) minuteList.disabled = isBlank;
		if (ampmList) ampmList.disabled = isBlank;
	}
}; 

(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) {return;}
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=259918514021950";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
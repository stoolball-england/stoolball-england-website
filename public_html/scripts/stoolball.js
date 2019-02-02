if (typeof (jQuery) != "undefined") {
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
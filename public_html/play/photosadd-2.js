Stoolball.Page =
{
	FileQueueError : function(fileObj, error_code, message) {
		try {
			var filename = fileObj.name.substr(0, fileObj.name.length-fileObj.type.length) + fileObj.type.toLowerCase();
			switch(error_code) {
				case SWFUpload.errorCode_QUEUE_LIMIT_EXCEEDED:
				message = "You've queued too many photos";
				break;
				case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
				message = filename + " is not a photo - it has no data";
				break;
				case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
				message = filename + " is too big. Photos must be 2MB or less";
				break;
				case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
				message = filename + " is not allowed. Only common web image formats (PNG, GIF, JPEG) are allowed";
				break;
			}

			Stoolball.Page.AddError(message);

		} catch (ex) { this.debug(ex); }

	},

	FileDialogComplete : function (num_files_selected, num_files_queued) {
		try {
			if (num_files_queued > 0) {
				this.startUpload();
			}
		} catch (ex) {
			this.debug(ex);
		}
	},

	UploadProgress : function(fileObj, bytesLoaded) {

		try {
			var percent = Math.ceil((bytesLoaded / fileObj.size) * 100)

			var progress = new Stoolball.Page.FileProgress(fileObj,  this.customSettings.upload_target);
			progress.setProgress(percent);
			if (percent === 100) {
				progress.setStatus("Creating thumbnail&#8230;");
				progress.toggleCancel(false, this);
			} else {
				progress.setStatus("Uploading&#8230;");
				progress.toggleCancel(true, this);
			}
		} catch (ex) { this.debug(ex); }
	},

	UploadSuccess : function(fileObj, server_data) {
		try {
			// upload.php returns the thumbnail id in the server_data, use that to retrieve the thumbnail for display
			if (server_data.indexOf('/images/thumbnails/') == 0)
			{
				Stoolball.Page.AddImage(server_data);

				var progress = new Stoolball.Page.FileProgress(fileObj,  this.customSettings.upload_target);
				progress.setStatus("Thumbnail created.");
				progress.toggleCancel(false);
			}
			else
			{
				Stoolball.Page.AddError('There was a problem saving the photo. Please check the file on your computer and try again.');
			}

		} catch (ex) { this.debug(ex); }
	},

	UploadComplete : function(fileObj) {
		try {
			/*  I want the next upload to continue automatically so I'll call startUpload here */
			if (this.getStats().files_queued > 0) {
				this.startUpload();
			} else {
				var progress = new Stoolball.Page.FileProgress(fileObj,  this.customSettings.upload_target);
				progress.setComplete();
				progress.setStatus("We've got all your photos. Now <strong>add your captions</strong> to finish.");
				progress.toggleCancel(false);
			}
		} catch (ex) { this.debug(ex); }
	},

	UploadError : function(fileObj, error_code, message) {
		try {
			switch(error_code) {
				case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
				try {
					progress = new Stoolball.Page.FileProgress(fileObj,  this.customSettings.upload_target);
					progress.setCancelled();
					progress.setStatus("Cancelled");
					progress.toggleCancel(false);
				}
				catch (ex) { this.debug(ex); }
				case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
				try {
					var progress = new Stoolball.Page.FileProgress(fileObj,  this.customSettings.upload_target);
					progress.setCancelled();
					progress.setStatus("Stopped");
					progress.toggleCancel(true);
				}
				catch (ex) { this.debug(ex); }
				case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
				default:
				break;
			}

			Stoolball.Page.AddError(message);

		} catch (ex) { this.debug(ex); }

	},


	/* ******************************************
	*	FileProgress Object
	*	Control object for displaying file info
	* ****************************************** */

	FileProgress : function(fileObj, target_id) {
		this.file_progress_id = "divFileProgress";

		this.fileProgressWrapper = document.getElementById(this.file_progress_id);
		if (!this.fileProgressWrapper) {
			this.fileProgressWrapper = document.createElement("div");
			this.fileProgressWrapper.className = "progressWrapper";
			this.fileProgressWrapper.id = this.file_progress_id;

			this.fileProgressElement = document.createElement("div");
			this.fileProgressElement.className = "progressContainer";

			var progressCancel = document.createElement("a");
			progressCancel.className = "progressCancel";
			progressCancel.href = "#";
			progressCancel.style.visibility = "hidden";
			progressCancel.appendChild(document.createTextNode(" "));

			var progressText = document.createElement("p");
			progressText.className = "progressName";
			progressText.appendChild(document.createTextNode(fileObj.name));

			var progressBar = document.createElement("div");
			progressBar.className = "progressBarInProgress";

			var progressStatus = document.createElement("p");
			progressStatus.className = "progressBarStatus";
			progressStatus.innerHTML = "&nbsp;";

			this.fileProgressElement.appendChild(progressCancel);
			this.fileProgressElement.appendChild(progressText);
			this.fileProgressElement.appendChild(progressStatus);
			this.fileProgressElement.appendChild(progressBar);

			this.fileProgressWrapper.appendChild(this.fileProgressElement);

			document.getElementById(target_id).appendChild(this.fileProgressWrapper);
			Stoolball.Page.FadeIn(this.fileProgressWrapper, 0);

		} else {
			this.fileProgressElement = this.fileProgressWrapper.firstChild;
			this.fileProgressElement.childNodes[1].firstChild.nodeValue = fileObj.name;
		}

		this.height = this.fileProgressWrapper.offsetHeight;

	},


	ClearErrors : function()
	{
		var validBox = document.getElementById('validationSummary');
		while (validBox.hasChildNodes()) validBox.removeChild(validBox.lastChild);
	},

	AddError : function(error_message)
	{
		var validBox = document.getElementById('validationSummary');
		if (validBox)
		{
			if (!validBox.hasChildNodes())
			{
				validBox.appendChild(document.createElement('ul'));
			}
			var summary = validBox.childNodes[0];
			if (summary)
			{
				var item = document.createElement('li');
				item.appendChild(document.createTextNode(error_message));
				summary.appendChild(item);
			}
		}
	},

	AddImage : function(src) {

		var thumbnails = document.getElementById("thumbnails");

		if (thumbnails)
		{
			var thumbContainer = document.createElement("div");
			thumbContainer.className = "thumbnail";
			var imageContainer = document.createElement("div");
			imageContainer.className = "photo thumbPhoto";
			var new_img = document.createElement("img");
			imageContainer.appendChild(new_img);
			thumbContainer.appendChild(imageContainer);

			var captionContainer = document.createElement('div');
			captionContainer.className = 'photoCaption thumbCaption';
			thumbContainer.appendChild(captionContainer);
			var captionBox = document.createElement('input');
			captionBox.setAttribute('type', 'text');
			captionBox.setAttribute('maxlength', '200');
			captionBox.setAttribute('id', 'caption' + (thumbnails.childNodes.length+1));
			captionBox.setAttribute('name', captionBox.getAttribute('id'));
			var captionLabel = document.createElement('label');
			captionLabel.setAttribute('for', captionBox.getAttribute('id'));
			captionLabel.appendChild(document.createTextNode('Add a caption: '));
			captionLabel.appendChild(captionBox);
			captionContainer.appendChild(captionLabel);

			// Word limit
			var words = document.createElement('span');
			words.className = 'wordLimit';
			words.appendChild(document.createTextNode('Up to 15 words'));
			$(captionBox).keydown(Stoolball.Page.WordCountDown);
			$(captionBox).keyup(Stoolball.Page.WordCountUp);
			captionLabel.appendChild(words);

			thumbnails.appendChild(thumbContainer);
			if (new_img.filters) {
				try {
					new_img.filters.item("DXImageTransform.Microsoft.Alpha").opacity = 0;
				} catch (e) {
					// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
					new_img.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + 0 + ')';
				}
			} else {
				new_img.style.opacity = 0;
			}

			new_img.onload = function () { Stoolball.Page.FadeIn(new_img, 0); };
			new_img.setAttribute('alt', 'You\'ve just uploaded this photo. Please add a caption.')
			new_img.src = src;

			// Hook up event to save caption
			$(captionBox).blur(Stoolball.Page.SaveCaption_Blur);

			// Focus on the first caption box
			Stoolball.Page.FocusCaption();
		}
	},

	// Focus on the first caption box
	FocusCaption : function()
	{
		var thumbnails = document.getElementById("thumbnails");
		var blanks = thumbnails.getElementsByTagName('input');
		if (blanks && blanks.length > 0)
		{
			/* After pressing Enter, this only works in Gecko. Others see removed
			textbox as 0, but won't let me detect the difference. */
			blanks[0].focus();
		}
	},

	WordCountDown : function(e)
	{
		// Treat Enter as submit
		if (e.keyCode === 13)
		{
			var thumbnails = this.parentNode.parentNode.parentNode.parentNode;
			Stoolball.Page.SaveCaption(this);
			setTimeout(Stoolball.Page.FocusCaption, 1000); // Enough time for Safari/IE to remove textbox from DOM
		}
		else
		{
			// If max words, ignore space key
			var left = Stoolball.Page.WordsLeft(this, 15);
			if (left < 1 && e.keyCode === 32)
			{
				e.preventDefault();
			}
		}
	},

	WordCountUp : function(e)
	{
		var left = Stoolball.Page.WordsLeft(this, 15);
		var message;
		if (left == 15) message = 'Up to 15 words';
		else if (left > 1) message = 'Up to ' + left + ' more words';
		else if (left == 1) message = '1 more word allowed';
		else if (left == 0) message = 'No more words allowed';
		else if (left < 0) message = 'Too many words!';

		while (this.nextSibling.hasChildNodes()) this.nextSibling.removeChild(this.nextSibling.firstChild);
		this.nextSibling.appendChild(document.createTextNode(message));
	},

	WordsLeft : function(textBox, words)
	{
		var spaces = /\s+/gi
		var ltrim = /^ (.*)$/gi
		var rtrim = /^(.*) $/gi
		var normalised = textBox.value.replace(spaces, ' ').replace(ltrim, '$1').replace(rtrim, '$1');
		var wordCount = (normalised.length) ? normalised.split(' ').length : 0;
		return (words-wordCount);
	},

	FadeIn : function(element, opacity) {
		var reduce_opacity_by = 15;
		var rate = 30;	// 15 fps


		if (opacity < 100) {
			opacity += reduce_opacity_by;
			if (opacity > 100) opacity = 100;

			if (element.filters) {
				try {
					element.filters.item("DXImageTransform.Microsoft.Alpha").opacity = opacity;
				} catch (e) {
					// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
					element.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + opacity + ')';
				}
			} else {
				element.style.opacity = opacity / 100;
			}
		}

		if (opacity < 100) {
			setTimeout(function() { Stoolball.Page.FadeIn(element, opacity); }, rate);
		}
	},

	SaveCaption_Blur : function(e)
	{
		Stoolball.Page.SaveCaption(this);
	},

	SaveCaption : function(textbox)
	{
		var trim = /\s*/gi
		var trimmed = textbox.value.replace(trim, '');
		if (trimmed && textbox.parentNode && textbox.parentNode.parentNode) /* If already saved, no parentNode */
		{
			var image = textbox.parentNode.parentNode.previousSibling.firstChild.getAttribute('src');
			var args = { image: image, caption: textbox.value, viewas: (document.getElementById('viewas') ? document.getElementById('viewas').getAttribute('value') : '')};
			var transaction = $.post('/play/photocaption.php',  args, function() { Stoolball.Page.SaveCaption_Success(textbox)});
		}
	},

	SaveCaption_Success : function(textBox)
	{
		// Make caption not-editable
		var caption = textBox.value;
		var captionContainer = textBox.parentNode.parentNode;
		textBox.parentNode.removeChild(textBox);
		captionContainer.removeChild(captionContainer.firstChild);
		var p = document.createElement('p');
		p.appendChild(document.createTextNode(caption));
		captionContainer.appendChild(p);
	},

	Load : function()
	{
		// Create the add photos button
		var degraded = document.getElementById('degraded_container');
		if (degraded)
		{
			$('#swfupload').append('<div id="validationSummary" class="validationSummary"></div>')
			.append('<div class="uploadPhotos"><span id="spanButtonPlaceholder"></span></div>')
			.append('<div id="divFileProgressContainer"></div><div id="thumbnails" class="thumbnails"></div>');
			$(degraded).remove();
		}

		new SWFUpload({
			// Backend Settings
			upload_url: "/play/photoupload.php",
			post_params:
			{
			"album" : document.getElementById('album') ? document.getElementById('album').getAttribute('value') : null,
			"viewas" : document.getElementById('viewas') ? document.getElementById('viewas').getAttribute('value') : null
			},

			// File Upload Settings
			file_size_limit : "2 MB",	// 2MB
			file_types : "*.png;*.gif;*.jpg;*.jpeg",
			file_types_description : "Common web image formats",
			file_upload_limit : "0",

			// Event Handler Settings - these functions as defined in Handlers.js
			//  The handlers are not part of SWFUpload but are part of my website and control how
			//  my website reacts to the SWFUpload events.
			file_queue_error_handler : Stoolball.Page.FileQueueError,
			file_dialog_complete_handler : Stoolball.Page.FileDialogComplete,
			upload_progress_handler : Stoolball.Page.UploadProgress,
			upload_error_handler : Stoolball.Page.UploadError,
			upload_success_handler : Stoolball.Page.UploadSuccess,
			upload_complete_handler : Stoolball.Page.UploadComplete,

			// Button Settings
			button_image_url : "/swfupload/page_add.png",
			button_placeholder_id : "spanButtonPlaceholder",
			button_width: 180,
			button_height: 20,
			button_text : '<span class="button">Select photos <span class="size">(up to 2MB)</span></span>',
			button_text_style : '.button { font-family: Helvetica, Arial, sans-serif; font-size: 14pt; } .size { font-size: 10pt; }',
			button_text_top_padding: 3,
			button_text_left_padding: 18,
			button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
			button_cursor: SWFUpload.CURSOR.HAND,

			// Flash Settings
			flash_url : "/swfupload/swfupload.swf",

			custom_settings : {
				upload_target : "divFileProgressContainer"
			},

			// Debug Settings
			debug: false
		});

		Stoolball.Page.FileProgress.prototype.setProgress = function (percentage) {
			this.fileProgressElement.className = "progressContainer green";
			this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
			this.fileProgressElement.childNodes[3].style.width = percentage + "%";
		};
		Stoolball.Page.FileProgress.prototype.setComplete = function () {
			this.fileProgressElement.className = "progressContainer blue";
			this.fileProgressElement.childNodes[3].className = "progressBarComplete";
			this.fileProgressElement.childNodes[3].style.width = "";

		};
		Stoolball.Page.FileProgress.prototype.setError = function () {
			this.fileProgressElement.className = "progressContainer red";
			this.fileProgressElement.childNodes[3].className = "progressBarError";
			this.fileProgressElement.childNodes[3].style.width = "";

		};
		Stoolball.Page.FileProgress.prototype.setCancelled = function () {
			this.fileProgressElement.className = "progressContainer";
			this.fileProgressElement.childNodes[3].className = "progressBarError";
			this.fileProgressElement.childNodes[3].style.width = "";

		};
		Stoolball.Page.FileProgress.prototype.setStatus = function (status) {
			this.fileProgressElement.childNodes[2].innerHTML = status;
		};

		Stoolball.Page.FileProgress.prototype.toggleCancel = function (show, swfuploadInstance) {
			this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
			if (swfuploadInstance) {
				var fileID = this.fileProgressID;
				this.fileProgressElement.childNodes[0].onclick = function () {
					swfuploadInstance.cancelUpload(fileID);
					return false;
				};
			}
		};
	}
}


$(document).ready(Stoolball.Page.Load);
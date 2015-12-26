 $(function()
{
	// Support placeholder text in older browsers
	if ($.browser.msie)
	{
		$("#from,#to").textPlaceholder();
	}
	else
	{
		$("#team,#opposition,#ground,#competition,#from,#to,#batpos").textPlaceholder();
	}

	// Add a calendar to help people pick dates
	$("#from,#to").datepicker({
		constrainInput : false,
		dateFormat : 'dd/mm/yy',
		maxDate : '+0d',
		onSelect : function(dateText, inst)
		{
			this.value = dateText;
			$(this).removeClass("text-placeholder");
		}
	});

	// Hide filter options by default
	function filterShow()
	{
		$("#statisticsFilter").slideDown();
		$(this).hide();
		$("#filter").val(1);
		$(".paging a").attr("href", function()
		{
			return this.href.replace("filter=0", "filter=1")
		});
	}
	function filterHide()
	{
		$("#statisticsFilter").slideUp();
		$(".panelShow").show();
		$("#filter").val(0);
		$(".paging a").attr("href", function()
		{
			return this.href.replace("filter=1", "filter=0")
		});
	}

	var filter = $("#statisticsFilter");
	filter.before($('<input type="button" value="Filter these statistics" class="panelShow" />').click(filterShow));
	if (document.URL.indexOf("filter=1") == -1)
	{
		filter.hide();
	}
	else
	{
		$(".panelShow").hide();
	}
	$("#statisticsFilter h2 span span span").prepend($('<input type="button" value="Hide this" class="panelHide" />').click(filterHide));
});

/**
 * @see http://github.com/NV/placeholder.js
 */
jQuery.fn.textPlaceholder = function()
{

	return this.each(function()
	{

		var that = this;

		if (that.placeholder && 'placeholder' in document.createElement(that.tagName)) return;

		var placeholder = that.getAttribute('placeholder');
		var input = jQuery(that);

		if (that.value === '' || that.value == placeholder)
		{
			input.addClass('text-placeholder');
			that.value = placeholder;
		}

		input.focus(function()
		{
			if (input.hasClass('text-placeholder'))
			{
				this.value = '';
				input.removeClass('text-placeholder')
			}
		});

		input.blur(function()
		{
			if (this.value === '')
			{
				input.addClass('text-placeholder');
				this.value = placeholder;
			}
			else
			{
				input.removeClass('text-placeholder');
			}
		});

		that.form && jQuery(that.form).submit(function()
		{
			if (input.hasClass('text-placeholder'))
			{
				that.value = '';
			}
		});

	});

};

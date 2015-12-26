$(function()
{
	// Hook up to role radios to enable/disable name editing
	$("input[name='Extras']").click(PlayerRole_Click);

	function PlayerRole_Click()
	{
		if (this.value == 0 && this.checked)
		{
			$("#Name").removeAttr("disabled");
		}
		else
		{
			$("#Name").attr("disabled", "disabled");
		}
	}

	// Run the radio code onload
	$("input[name='Extras']:checked").each(PlayerRole_Click);

	// Select the player name for editing, or if that's disabled select the team
	if ($("#Merge").length == 1)
	{
		$("#Merge")[0].focus();
		$("#playerEditorFields").hide();
		$("#Merge, #Rename").click(function(){ if ($("#Rename")[0].checked) $("#playerEditorFields").slideDown(); else $("#playerEditorFields").slideUp();});
	}
	else
		if ($("#Name").attr("disabled") == false)
		{
			$("#Name").select();
		}
		else
		{
			$("#Team").focus();
		}
});
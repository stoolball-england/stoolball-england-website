$(function()
{
	// Select the player name for editing
	if ($("#Merge").length == 1)
	{
		$("#Merge")[0].focus();
		$("#playerEditorFields").hide();
		$("#Merge, #Rename").click(function(){ if ($("#Rename")[0].checked) $("#playerEditorFields").slideDown(); else $("#playerEditorFields").slideUp();});
	}
	else
	{
		$("#Name").select();
	}
});
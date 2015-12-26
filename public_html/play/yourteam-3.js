$(document).ready(function()
{
		$('#publicContactPart').hide();
		$('#publicContact input:radio').click(Stoolball.Page.PublicRadio_Click);
});

Stoolball.Page =
{
	PublicRadio_Click : function(e)
	{
		if (document.getElementById('publicDiff').checked)
		{
			$('#publicContactPart').slideDown('slow');
		}
		else
		{
			$('#publicContactPart').slideUp('slow');
		}
	}
}

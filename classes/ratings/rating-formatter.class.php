<?php
class RatingFormatter
{
	function GetStatusText($i_average)
	{
		return $i_average ? 'Rated ' . $i_average . '/10' :  'Not rated yet';
	}
	
	function GetListText($i_average)
	{
		if ($i_average) return 'Rated ' . $i_average . '/10'; else return 'Be the first to rate it!';
	}
}
?>
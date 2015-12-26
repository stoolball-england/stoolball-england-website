<?php 
class RatingBreakdown
{
	var $o_rated_item;
	var $a_stats;
	
	function RatingBreakdown($o_input=null)
	{
		# initialise array with 0 of each rating
		for ($i_count = 0; $i_count < 10; $i_count++) $this->a_stats[$i_count] = 0;

		# add item if supplied
		if ($o_input) $this->SetRatedItem($o_input);
	}
	
	function SetRatedItem($o_input)
	{
		if (is_object($o_input)) 
		{
			$this->o_rated_item = $o_input;
			$this->generate_breakdown_stats();
		}
	}
	
	function GetRatedItem()
	{
		return $this->o_rated_item;
	}
	
	function generate_breakdown_stats()
	{
		if(is_array($this->o_rated_item->GetRatings()))
		{
			foreach($this->o_rated_item->GetRatings() as $o_rating)
			{
				# higher weightings indicate ratings transferred from before registration required
				# not 100% accurate because some transfers has weighting of 1, but doesn't matter
				if ($o_rating->GetWeighting() == 1)
				{
					$this->a_stats[$o_rating->GetRating()-1] = $this->a_stats[$o_rating->GetRating()-1] + $o_rating->GetMultiplier();
				}
			}
		}
	}

	function GetCompleteBreakdown()
	{
		if (is_array($this->a_stats))
		{
			$s_text = '<table class="ratingBreakdown" summary="Breakdown of results - totals and percentages for each rating">' . "\n" .
				'<caption>Ratings breakdown</caption>' . "\n" . 
				'<thead><tr><th class="rating-value">Rating</th><th>Percentage</th><th class="rating-votes">Total</th></tr></thead>' . "\n" .
				'<tbody>' . "\n";
			
			for ($i_count = 0; $i_count < 10; $i_count++)
			{
				# get percent, but protect from div by 0
				$i_percentage = $this->o_rated_item->GetTotalRatings() ? round(($this->a_stats[$i_count] / $this->o_rated_item->GetTotalRatings()) * 100) : 0;
				$i_bar_length = $i_percentage*4;
				$s_percentage = '<span class="percent">' . $i_percentage . '<span class="percent-sign">%</span></span>';
				
				$s_text .= '<tr><td class="rating-value">' . ($i_count+1) . '</td><td class="rating-bar"><div class="rating-bar" style="width:' . $i_bar_length . 'px;">' . $s_percentage . '</div></td><td class="rating-votes">' . $this->a_stats[$i_count] . ' ';
				if ($this->a_stats[$i_count] == 1) $s_text .= 'rating'; else $s_text .= 'ratings';
				$s_text .= '</td></tr>' ."\n";
			}
			
			# get new overall stats, not counting transferred entries with high weighting
			$i_percentage = $this->o_rated_item->GetAverageRating() * 10;
			$i_bar_length = $i_percentage*4;
			$s_percentage = '<span class="percent">' . $i_percentage . '<span class="percent-sign">%</span></span>';
	
			$s_text .= '<tr class="overall"><td class="rating-value">Overall</td><td class="rating-bar"><div class="rating-bar" style="width:' . $i_bar_length . 'px;">' . $s_percentage . '</div></td><td class="rating-votes">' . $this->o_rated_item->GetTotalRatings() . ' ratings</td></tr>' ."\n" .
				'</tbody>' . "\n" . 
				'</table>' . "\n\n";
			
			if ($this->o_rated_item->GetOldRatings()) $s_text .= '<p><small>Note: Detailed figures do not include ' . $this->o_rated_item->GetOldRatings() . ' ratings from before detailed statistics were recorded.</small></p>' . "\n\n";
		}
	
		return $s_text;
	}
}
?>
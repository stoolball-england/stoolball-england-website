<?php
/**
 * Utility functions for working with CSV files
 * @author Rick
 *
 */
class CSV
{
	/**
	 * Publish an array of arrays as a CSV download
	 * @param array[array] $data
	 * @throws Exception
	 */
	public static function PublishData($data)
	{
		# Check that the data is an array
		if (!is_array($data)) throw new Exception('$data must be an array of arrays');

		# Respond with HTTP headers that download this data is a file
		# and allow it to be cached for up to one hour
		header("Content-type: text/csv");
		header("Cache-Control: max-age=3600, public");
		header('Content-Disposition: attachment; filename="stoolball-england.csv"');

		# Use PHP function to get the CSV format right
		$outstream = fopen("php://output",'w');
        
        # Add Unicode BOM to ensure correct encoding, but only needed locally as no problem on live,
        # but this causes one. http://www.24k.com.sg/blog-55.html
        if (SiteContext::IsDevelopment())
        {
           fprintf($outstream, chr(0xEF).chr(0xBB).chr(0xBF));
        }

		foreach($data as $row)
		{
			fputcsv($outstream, $row, ',', '"');
		}

		fclose($outstream);
		exit();
	}
}
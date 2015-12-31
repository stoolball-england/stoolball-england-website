<?php
/**
 * Manage a process split into multiple batches to avoid timeouts
 * @author Rick
 *
 */
class ProcessManager
{
	private $offset;
	private $batch_size;
	private $total = 0;
	private $process_id;

	/**
	 * Creates a ProcessManager
	 * @param string $process_id
     * @param int $batch_size
	 */
	public function __construct($process_id='', $batch_size=100)
	{
		$this->offset = (isset($_POST['offset']) and is_numeric($_POST['offset'])) ? $_POST['offset'] : "0";
		$this->process_id = $process_id;
        $this->batch_size = (int)$batch_size;
	}

	/**
	 * Checks whether we are at the start of a process, and should clear all existing records
	 */
	public function ReadyToDeleteAll()
	{
		return ($this->offset == 0);
	}

	/**
	 * Get a LIMIT clause to restrict the source data to the current batch being processed
	 */
	public function GetQueryLimit()
	{
		return " LIMIT " . $this->offset . ", " . $this->batch_size;
	}

	/**
	 * Increment the total number of items processed
	 */
	public function OneMoreDone()
	{
		$this->total++;
	}

	/**
	 * Print HTML to tell the user what's going on
	 */
	public function ShowProgress()
	{
		if ($this->total == $this->batch_size)
		{
			echo "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\" id=\"process\"><div>
			<input type=\"hidden\" name=\"offset\" value=\"" . ($this->offset + $this->batch_size) . "\"  />
			<input type=\"hidden\" name=\"" . $this->process_id . "\" value=\"1\" />
			<script>setTimeout('document.getElementById(\'process\').submit()', 1000) </script>
			<p>Done " . ($this->offset + $this->total) . ". Working &#8230;</p>
			</div></form>";
		}
		else
		{
			echo "<p>Done.</p>";
		}
	}
}
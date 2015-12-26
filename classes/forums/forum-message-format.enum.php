<?php 
class ForumMessageFormat
{
	/* an enum of formats */
	public static function Standard() { return 1; }
	public static function NewTopic() { return 2; }
	public static function Reply() { return 3; }
	public static function Review() { return 4; }
	public static function ReviewReply() { return 5; }
	public static function ReviewAndRate() { return 6; }
	public static function ReviewAndRateReply() { return 7; }
}
?>
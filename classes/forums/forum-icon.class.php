<?php 
require_once('xhtml/xhtml-image.class.php');

class ForumIcon extends XhtmlImage
{
	public function __construct($o_settings, $s_image_url)
	{
		parent::__construct($o_settings, $s_image_url);
		$this->SetCssClass('forumIcon');
        $description = basename($s_image_url);
		$this->SetDescription(ucfirst(str_replace('_',' ',substr($description,0,strlen($description)-4))));
	}
}
?>
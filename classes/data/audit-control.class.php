<?php
require_once ("xhtml/xhtml-element.class.php");
require_once ("audit-data.class.php");

/**
 * Display who last edited a thing and when
 */
class AuditControl extends XhtmlElement
{
	private $audit;
	private $thing;

	public function __construct(AuditData $audit, $thing)
	{
		$this->audit = $audit;
		$this->thing = $thing;
		parent::__construct("p");
	}

	public function OnPreRender()
	{
		$this->AddControl("This " . htmlentities($this->thing, ENT_QUOTES, "UTF-8", false) . " was last saved by " . 
		      $this->audit->GetUser()->GetRelativeName() . ' at ' . Date::BritishDateAndTime($this->audit->GetTime()) . '.');
		$this->SetCssClass('formPart audit');
	}

}
?>
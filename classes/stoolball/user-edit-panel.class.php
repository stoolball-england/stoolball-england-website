<?php
require_once('xhtml/xhtml-element.class.php');

/**
 * Panel listing actions users can take to update the current page
 *
 */
class UserEditPanel extends XhtmlElement
{
	private $o_settings;
	private $a_links;
	private $s_about;

	/**
	 * Instantiates a UserEditPanel
	 *
	 * @param SiteSettings $o_settings
	 * @param string $s_about
	 */
	public function __construct(SiteSettings $o_settings, $s_about='')
	{
		parent::__construct('div');
		$this->o_settings = $o_settings;
		$this->a_links = array();
		$this->s_about = (string)$s_about;
	}

	/**
	 * Add a link to the panel
	 *
	 * @param string $s_text
	 * @param string $s_url
	 */
	public function AddLink($s_text, $s_url, $class="")
	{
		$link = new XhtmlAnchor(htmlentities($s_text, ENT_QUOTES, "UTF-8", false), $s_url);
        $item = new XhtmlElement('li', $link);
        if ($class) $item->AddCssClass($class);
		$this->a_links[] = $item;
	}

	public function OnPreRender()
	{
		# Create panel
		$o_sidebar = new XhtmlElement('div');
		$o_outer_panel = new XhtmlElement('div', $o_sidebar);
		$this->AddControl($o_outer_panel);
		$this->AddCssClass('panel supportingPanel screen');

		# Add heading
		$o_sidebar->AddControl(new XhtmlElement('h2', '<span><span><span>You can<span class="aural"> take these actions</span>:</span></span></span>'));

		# Create list for links
		$o_ul = new XhtmlElement('ul');
		$o_sidebar->AddControl($o_ul);

		# Add custom links
		foreach ($this->a_links as $item)
		{
			$o_ul->AddControl($item);
		}

		# Link to contact us
		$text = '<a href="' . $this->o_settings->GetFolder('Contact') . '">contact Stoolball England</a>';
		$o_contact = new XhtmlElement('li', $text, "large");
		$o_ul->AddControl($o_contact);
	}
}
?>
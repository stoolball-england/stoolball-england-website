<?php
require_once('xhtml/xhtml-element.class.php');

/**
 * A link on a web page
 *
 */
class XhtmlAnchor extends XhtmlElement
{
	public function XhtmlAnchor($s_text='', $s_navigate_url='')
	{
		parent::XhtmlElement('a');
		$this->AddControl($s_text);
		$this->SetNavigateUrl($s_navigate_url);
	}

	/**
	* @return void
	* @param string $s_text
	* @desc Sets the URL to link to
	*/
	public function SetNavigateUrl($s_text)
	{
		$this->AddAttribute('href', $s_text);
	}

	/**
	* @return string
	* @desc Gets the URL to link to
	*/
	public function GetNavigateUrl()
	{
		return $this->GetAttribute('href');
	}

	/**
	 * Builds the child controls ready to be added to the control tree
	 *
	 */
	protected function OnPreRender()
	{
		# A link with no URL is no link
		if (!$this->GetNavigateUrl())
		{
			$this->SetVisible(false);
			return;
		}

		# If this control has child controls already, do nothing
		if ($this->CountControls()) return;

		# If link is just a domain, use that as the link text
		$s_url = urldecode($this->GetNavigateUrl());
		if (substr_count($s_url, '/') < 4)
		{
			$this->AddControl($s_url);
			return;
		}

		# If link is longer, trim it
		$i_max_length = 40;
		if (strlen($s_url) > $i_max_length)
		{
			$a_url_parts = explode('/', $s_url);
			$i_parts = count($a_url_parts)-1;
			$i = 0;
			$s_end = '/&#8230;/' . $a_url_parts[$i_parts];
			$i_end_length = strlen($a_url_parts[$i_parts]+1);
			$i_parts--;
			$s_url = '';

			while ($i < $i_parts)
			{
				if ($i == 0)
				{
					$s_longer = $a_url_parts[$i];
				}
				else
				{
					$s_longer = $s_url . '/' . $a_url_parts[$i];
				}
				if ((strlen($s_longer) + $i_end_length) <= $i_max_length)
				{
					$s_url = $s_longer;
				}
				else break;
				$i++;
			}

			$s_url .= $s_end;
		}

		$this->AddControl($s_url);
	}

}
?>
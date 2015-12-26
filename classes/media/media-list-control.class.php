<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/placeholder.class.php');

class MediaListControl extends Placeholder
{
	private $gallery_items;
	var $s_title;
	var $s_heading;
	var $s_list_css;

	function MediaListControl($gallery_items=null)
	{
		parent::Placeholder();
		$this->gallery_items = (is_array($gallery_items)) ? $gallery_items : array();
	}

	/**
	 * @return void
	 * @param string $s_heading
	 * @desc Sets the heading of the media list
	 */
	function SetHeading($s_heading)
	{
		$this->s_heading = (string)$s_heading;
	}


	/**
	 * @return string
	 * @desc Gets the heading of the media list
	 */
	function GetHeading()
	{
		return $this->s_heading;
	}

	/**
	 * @return void
	 * @param string $s_title
	 * @desc Sets the title of the media list
	 */
	function SetTitle($s_title)
	{
		$this->s_title = (string)$s_title;
	}

	/**
	 * @return string
	 * @desc Gets the title of the media list
	 */
	function GetTitle()
	{
		return $this->s_title;
	}

	/**
	 * @return void
	 * @param string $s_class
	 * @desc Sets the CSS class of the unordered list
	 */
	function SetListCssClass($s_class)
	{
		$this->s_list_css = (string)$s_class;
	}

	/**
	 * @return string
	 * @desc Gets the CSS class of the unordered list
	 */
	function GetListCssClass()
	{
		return $this->s_list_css;
	}

	function OnPreRender()
	{
		$o_ul = new XhtmlElement('ul');
		if ($this->s_list_css) $o_ul->SetCssClass($this->s_list_css);
		$i = 0;
		foreach ($this->gallery_items as $gallery_item)
		{
			$o_item = ($gallery_item instanceof GalleryItem) ? $gallery_item->GetItem() : $gallery_item;
			$i++;
			$s_class = strtolower(get_class($o_item));

			if ($s_class == 'xhtmlimage')
			{
                /* @var $o_item XhtmlImage */

				# Get URL of image in gallery
				$s_page = $gallery_item->GetUrl();

				$o_thumb = $o_item->GetThumbnail();

				$o_link = new XhtmlElement('a', $o_thumb);
				$o_link->AddAttribute('href', $s_page);
				$o_link->SetCssClass('photo thumbPhoto');

				$captionlink = $o_item->GetDescription() ? new XhtmlAnchor(htmlentities($o_item->GetDescription(), ENT_QUOTES, "UTF-8", false), htmlentities($s_page, ENT_QUOTES, "UTF-8", false)) : null;
				$caption = new XhtmlElement('p', $captionlink);
				$captionbox = new XhtmlElement('div', $caption);
				$captionbox->SetCssClass('photoCaption thumbCaption');

				$o_li = new XhtmlElement('li');
				$o_li->SetCssClass('thumbnail');
				$o_li->AddControl($o_link);
				$o_li->AddControl($captionbox);

				$o_ul->AddControl($o_li);
			}
        }

		if ($o_ul->CountControls())
		{
			if ($this->GetHeading())
			{
				$o_h = new XhtmlElement('h2', htmlentities($this->GetHeading(), ENT_QUOTES, "UTF-8", false));
				$this->AddControl($o_h);
			}
			if ($this->GetTitle())
			{
				$o_p = new XhtmlElement('p', htmlentities($this->GetTitle(), ENT_QUOTES, "UTF-8", false));
				$this->AddControl($o_p);
			}
			$this->AddControl($o_ul);
		}
	}
}
?>
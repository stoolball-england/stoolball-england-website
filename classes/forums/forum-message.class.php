<?php
require_once('markup/xhtml-markup.class.php');
require_once('text/bad-language-filter.class.php');
require_once('email/email-address-protector.class.php');

/**
 * A message posted in a forum
 *
 */
class ForumMessage
{
	private $o_settings;
	private $o_user;
	private $o_filter;
	private $i_message_id;
	private $s_topic_title;
	private $s_topic_title_filtered;
	private $o_category;
	private $s_title;
	private $s_title_filtered;
	private $s_title_formatted;
	private $i_date;
	private $o_person;
	private $s_body;
	private $s_body_filtered;
	private $s_body_formatted;
    private $review_item;

	/**
	 * Creates a new instance of ForumMessage
	 *
	 * @param SiteSettings $o_settings
	 * @param User $o_user
	 */
	public function __construct(SiteSettings $o_settings, User $o_user)
	{
		$this->o_settings = $o_settings;
		$this->o_user = $o_user;
	}

	function SetId($i_input)
	{
		if (is_numeric($i_input)) $this->i_message_id = (int)$i_input;
	}

	function GetId()
	{
		return $this->i_message_id;
	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the title of the forum topic
	 */
	function SetTopicTitle($s_input)
	{
		$this->s_topic_title = (string)$s_input;

		# Reset to force re-filtering
		$this->s_topic_title_filtered;
	}

	/**
	 * @return string
	 * @desc Gets the title of the forum topic
	 */
	function GetTopicTitle()
	{
		return $this->s_topic_title;
	}

	/**
	 * @return string
	 * @desc Gets the title of the forum topic with swear filtering applied
	 */
	function GetFilteredTopicTitle()
	{
		if (!$this->s_topic_title_filtered)
		{
			if (!is_object($this->o_filter)) $this->o_filter = new BadLanguageFilter();
			$this->s_topic_title_filtered = $this->o_filter->Filter($this->GetTopicTitle());
		}
		return $this->s_topic_title_filtered;
	}

	function SetCategory(Category $o_input)
	{
		$this->o_category = $o_input;
	}

	function GetCategory()
	{
		return $this->o_category;
	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the title of the forum message
	 */
	function SetTitle($s_input)
	{
		if (is_string($s_input))
		{
			$this->s_title = $s_input;

			# Reset to force re-filtering/formatting
			$this->s_title_filtered = '';
			$this->s_title_formatted = '';
		}
	}

	/**
	 * @return string
	 * @param bool $b_force
	 * @desc Gets the title of the forum message
	 */
	function GetTitle($b_force=false)
	{
		# default to using this message's title
		$s_text = $this->s_title;

		# can force every message to have a title by using topic title
		if (!$s_text and $b_force) $s_text = $this->GetTopicTitle();

		return $s_text;
	}

	/**
	 * @return string
	 * @param bool $b_force
	 * @desc Gets the title of the forum message with swear filtering applied
	 */
	function GetFilteredTitle($b_force=false)
	{
		if (!$this->s_title_filtered)
		{
			if (!is_object($this->o_filter)) $this->o_filter = new BadLanguageFilter();
			$this->s_title_filtered = $this->o_filter->Filter($this->GetTitle());

			# can force every message to have a title by using topic title
			if (!$this->s_title_filtered and $b_force)
			{
				return $this->GetFilteredTopicTitle();
			}
		}
		return $this->s_title_filtered;
	}

	/**
	 * @return string
	 * @desc Gets the title of the forum message with swear filtering and HTML encoding applied
	 */
	function GetFormattedTitle()
	{
		if (!$this->s_title_formatted)
		{
			$this->s_title_formatted = htmlentities($this->GetFilteredTitle(), ENT_QUOTES, "UTF-8", false);
		}
		return $this->s_title_formatted;
	}

	/**
	 * Sets the date of the message as a UNIX timestamp
	 * @param int $i_date
	 * @return void
	 */
	public function SetDate($i_date)
	{
		if (is_numeric($i_date)) $this->i_date = (int)$i_date;
	}

	/**
	 * Gets the date of the message as a UNIX timestamp
	 * @return int
	 */
	public function GetDate()
	{
		return $this->i_date;
	}

	function SetUser(User $o_input)
	{
		$this->o_person = $o_input;
	}

	function GetUser()
	{
		return $this->o_person;
	}

	function SetBody($s_input)
	{
		$this->s_body = (string)$s_input;

		# Reset to force re-filtering/formatting on request
		$this->s_body_filtered = '';
		$this->s_body_formatted = '';
	}

	/**
	 * @return string
	 * @desc Gets the body text of the forum message
	 */
	function GetBody()
	{
		return $this->s_body;
	}

	/**
	 * @return string
	 * @desc Gets the body text of the forum message with swear filtering applied
	 */
	function GetFilteredBody()
	{
		if (!$this->s_body_filtered)
		{
			if (!is_object($this->o_filter)) $this->o_filter = new BadLanguageFilter();
			$this->s_body_filtered = $this->o_filter->Filter($this->GetBody());
		}
		return $this->s_body_filtered;
	}

	/**
	 * @return string
	 * @desc Gets the body text of the forum message with swear filtering and formatting applied
	 */
	function GetFormattedBody(SiteSettings $o_site_settings)
	{
		if (!$this->s_body_formatted) $this->FormatBody($o_site_settings);
		return $this->s_body_formatted;
	}

	private function FormatBody(SiteSettings $o_site_settings, $b_strip_tags=false)
	{
		if ($this->GetFilteredBody())
		{
			$text = $this->GetFilteredBody();

			# strip attributes
			$text = preg_replace('/ (align|style|lang|xml:lang|class)=".*?"/', '', $text);
			
			# strip unwanted tags
			$text = preg_replace('/<\/?(div|span|sup)[^>]*>/', '', $text);

			# protect emails before escaping HTML, because it's trying to recognise the actual HTML tags
			$protector = new EmailAddressProtector($this->o_settings);
			$text = $protector->ApplyEmailProtection($text, $this->o_user->IsSignedIn());

			$text = htmlentities($text, ENT_QUOTES, "UTF-8", false);

			$text = XhtmlMarkup::ApplyCharacterEntities($text);
			$text = XhtmlMarkup::ApplyParagraphs($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplyLinks($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplyLists($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplySimpleTags($text, $b_strip_tags);
			$text = XhtmlMarkup::ApplySimpleXhtmlTags($text, $b_strip_tags);
			$text = XhtmlMarkup::CloseUnmatchedTags($text);
		}

		$this->s_body_formatted = $text;
	}

	function GetExcerpt()
	{
		# get an excerpt of the message -- strip formatting tags
		$s_text = "";
		if ($this->GetFilteredBody())
		{
			$s_text = XhtmlMarkup::ApplyCharacterEntities($this->GetFilteredBody());
			$s_text = XhtmlMarkup::ApplyLinks($s_text, true);
			$s_text = XhtmlMarkup::ApplyLists($s_text, true);
			$s_text = XhtmlMarkup::ApplySimpleTags($s_text, true);
			$s_text = XhtmlMarkup::ApplySimpleXhtmlTags($s_text, true);
			$s_text = XhtmlMarkup::CloseUnmatchedTags($s_text, true);
			$s_text = str_replace("\n", '', $s_text); # collapse new lines

			if (strlen($s_text) > 200)
			{
				$s_text = substr($s_text, 0, 200);
				$pos = strrpos($s_text, " ");
				if ($pos > 0) $s_text = substr($s_text, 0, $pos); # cut off any half-words
				$s_text .=  '...';  # plain text - don't use real ellipsis
			}
			$s_text = strip_tags(html_entity_decode($s_text, ENT_QUOTES)); # plain text
		}

		return $s_text;
	}

    /**
     * Gets the URI which uniquely identifies this message
     */
    public function MessageLinkedDataUri()
    {
        if (!$this->review_item instanceof ReviewItem) {
            throw new Exception("You must set the review item to get the linked data ID");
        }
        return $this->review_item->GetLinkedDataUri() . '/comments/' . $this->GetId();
    }
    
    /**
     * Sets the item this message is commenting on
     */
    public function SetReviewItem(ReviewItem $review_item) {
        $this->review_item = $review_item;
    }
    
    /**
     * Gets the item this message is commenting on
     */
    public function GetReviewItem() {
        return $this->review_item;
    }
}
?>
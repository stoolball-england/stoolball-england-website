<?php
require_once('category/category-collection.class.php');

class XhtmlMarkup
{
	/**
	 * @return string
	 * @param string $s_text
	 * @desc Correct invalid (eg Word) characters to XHTML characters and entities
	 */
	public static function ApplyCharacterEntities($s_text)
	{
		# curly quotes
		$s_text = str_replace("�", "'", $s_text);
		$s_text = str_replace("�", "'", $s_text);
		$s_text = str_replace('�', '&quot;', $s_text);
		$s_text = str_replace('�', '&quot;', $s_text);

		# other invalid chars
		$s_text = str_replace('�', '&#8211;', $s_text);
		$s_text = str_replace('&amp;#', '&#', $s_text);

		return $s_text;
	}

	/**
	 * @return string
	 * @param string $text
	 * @param bool $b_strip_tags
	 * @desc Converts text with line breaks into XHTML paragraphs
	 */
	public static function ApplyParagraphs($text, $b_strip_tags=false)
	{
		if (!strlen($text)) return $text;

		# Need to decide whether paragraphs have already been specified using HTML.
		# Just look for any HTML. If it's there, assume paragraphs are too.
		# Otherwise convert new lines, but can't do both because we end up with too many tags.
		if (strpos($text, "&lt;") !== false)
		{
			# Restore escaped HTML paragraphs
			$replace = $b_strip_tags ? "" : "<$1p>";
			$text = preg_replace('/&lt;(\/?)p( .*?)?&gt;/', $replace, $text);
			$replace = $b_strip_tags ? "" : "<br />";
			$text = preg_replace('/&lt;br \/&gt;/', $replace, $text);

			# But we never want empty paragraphs
			$text = str_replace("<p>&nbsp;</p>", "", $text);
			$text = preg_replace('/<p>\s*<\/p>/', "", $text);
		}
		else
		{
			# Convert new line characters when text stored without HTML
			$text = preg_replace("/\r/", '', $text);
			$text = preg_replace("/\n\n/", "</p><p>", $text);
			$text = nl2br($text);
			$text = preg_replace("/<\/p><p>/", "</p>\n\n<p>", $text);
			$text = '<p>' . $text . "</p>\n\n";
		}

		return $text;
	}


	/**
	 * @return string
	 * @param string $s_text
	 * @desc Converts escaped XHTML for a table back into XHTML
	 */
	public static function ApplyTables($s_text)
	{
		# XHTML table must be enclosed within these tags
		$s_open_tag = '[xhtmltable]';
		$s_close_tag = '[/xhtmltable]';
		$i_len_open = strlen($s_open_tag);
		$i_len_close = strlen($s_close_tag);

		# for each table in $s_text
		$i_start_pos = strpos($s_text, $s_open_tag);
		while($i_start_pos !== false)
		{
			# divide $s_text into stuff before the table, the table, and stuff after
			$i_end_pos = strpos($s_text, $s_close_tag);
			$s_text_before = rtrim(substr($s_text, 0, $i_start_pos));
			$s_text_table = substr($s_text, $i_start_pos+$i_len_open, $i_end_pos-$i_start_pos-$i_len_open);
			$s_text_after = ltrim(substr($s_text, $i_end_pos+$i_len_close));

			# if XhtmlMarkup::ApplyParas has been run, tidy up its effect on the table
			$i_len_before = strlen($s_text_before)-3;
			if (substr($s_text_before, $i_len_before) == '<p>') $s_text_before = substr($s_text_before, 0, $i_len_before);
			$s_text_table = str_replace("<br />\n", "\n", $s_text_table);
			if (substr($s_text_after, 0, 4) == '</p>') $s_text_after = substr($s_text_after, 4);

			# Attribute values must match this pattern
			$s_attr_value = '[A-Za-z0-9 -\':?!.,;\/)(]+';

			# Only these attributes are allowed at some point in the table
			$s_text_table = preg_replace('/(summary|class|scope|abbr|span|colspan|rowspan|id|headers)=&quot;(' . $s_attr_value . ')&quot;/', '$1="$2"', $s_text_table);

			# Opening table tag can have two attributes
			$s_permitted_attr = '( (class|summary)="' . $s_attr_value . '")';
			$s_text_table = preg_replace('/&lt;(\/)?table' . $s_permitted_attr . '?' . $s_permitted_attr . '?&gt;/', '<$1table$2$4>', $s_text_table);

			# Opening th tag must have one attribute, can have four
			$s_permitted_attr = '( (class|scope|id|headers|rowspan)="' . $s_attr_value . '")';
			$s_text_table = preg_replace('/&lt;(\/)?th' . $s_permitted_attr . $s_permitted_attr . '?' . $s_permitted_attr . '?' . $s_permitted_attr . '?' . $s_permitted_attr . '?&gt;/', '<$1th$2$4>', $s_text_table);

			# Opening td tag can have one attribute
			$s_permitted_attr = '( (headers)="' . $s_attr_value . '")';
			$s_text_table = preg_replace('/&lt;(\/)?td' . $s_permitted_attr . '?&gt;/', '<$1td$2$4>', $s_text_table);

			# Simple conversion of these tags - no attributes
			$s_text_table = preg_replace('/&lt;(\/)?(thead|tbody|caption|tr)&gt;/', '<$1$2>', $s_text_table);

			# Closing tags for elements which require attributes
			$s_text_table = preg_replace('/&lt;\/(table|th)&gt;/', '</$1>', $s_text_table);

			# Some entities allowed
			$s_text_table = str_replace('&amp;minus;', '&minus;', $s_text_table);

			# Join the processed text back together
			$s_text = $s_text_before . $s_text_table . $s_text_after;

			# check for another table
			$i_start_pos = strpos($s_text, '[xhtmltable]');
		}

		return $s_text;
	}

	# run xhtml into an unbroken text stream
	public static function CollapseXhtmlParagraphs($s_text)
	{
		$s_text = str_replace('<br />','',$s_text);
		$s_text = str_replace('<p>','',$s_text);
		$s_text = str_replace('</p>','',$s_text);

		return $s_text;
	}

	/**
	 * @return string
	 * @param string $s_text
	 * @param bool $b_strip_tags
	 * @desc Apply bold, italic, sup, pre, cite, q, acronym and abbr
	 */
	public static function ApplySimpleTags($s_text, $b_strip_tags=false)
	{
		$s_replace_with = $b_strip_tags ? "" : "<\\1strong>";
		$s_text = preg_replace("/\[(\/?)b]/", $s_replace_with, $s_text);

		$s_replace_with = $b_strip_tags ? "" : "<\\1em>";
		$s_text = preg_replace("/\[(\/?)i]/", $s_replace_with, $s_text);

		$s_replace_with = $b_strip_tags ? "" : "<\\1\\2>";
		$s_text = preg_replace("/\[(\/?)(sup|pre|cite|q|em|strong)]/", $s_replace_with, $s_text);

		$s_replace_with = $b_strip_tags ? "$3" : "<$1 title=\"$2\">\$3</$1>";
		$s_text = preg_replace("/\[(acronym|abbr)=([A-Za-z0-9-,' )(#&;]+)]([A-Za-z0-9-,' ()]+)\[\/(acronym|abbr)]/", $s_replace_with, $s_text);

		$s_replace_with = $b_strip_tags ? '' : "<$1 class=\"$2\">";
		$s_text = preg_replace("/\[(q|em|strong)=([A-Za-z0-9-]+)]/", $s_replace_with, $s_text);

		return $s_text;
	}

	/**
	 * Converts heading markup into XHTML equivalents. Apply after paragraphs.
	 *
	 * @param string $s_text
	 * @param bool $b_strip_tags
	 */
	public static function ApplyHeadings($s_text, $b_strip_tags=false)
	{
		$s_replace_with = $b_strip_tags ? "" : "<$1h$2>";
		$s_text = preg_replace("/\[(\/?)h([0-6])]/", $s_replace_with, $s_text);

		# If ApplyParas was used, format will now be <p><h2>xyz</h2></p>
		if (!$b_strip_tags)
		{
			$s_text = preg_replace("/<p[^>]*><h([0-9])>/", "<h$1>", $s_text);
			$s_text = preg_replace("/<\/h([0-9])><\/p>/", "</h$1>", $s_text);
		}

		return $s_text;
	}

	# unescape limited range of simple xhtml tags
	public static function ApplySimpleXhtmlTags($s_text, $b_strip_tags)
	{
		$s_replace_with = $b_strip_tags ? '' : "<\\1strong>";
		$s_text = preg_replace("/&lt;(\/?)(b|strong)&gt;/i", $s_replace_with, $s_text);

		$s_replace_with = $b_strip_tags ? '' : "<\\1em>";
		$s_text = preg_replace("/&lt;(\/?)(i|em)&gt;/i", $s_replace_with, $s_text);

		return $s_text;
	}

	/**
	 * Parses [link] and [url] tags into links
	 *
	 * @param $text: Text which may contain [link] or [url] tags
	 * @param $b_strip_tags: false to convert tags to links, true to strip tags
	 * @return Text with links converted or stripped
	 */
	public static function ApplyLinks($text, $b_strip_tags=false)
	{
		# Catch custom tags
		$replace_with = $b_strip_tags ? '' : "<a href=\"\\1\">";
		$text = preg_replace("/\[url=([^[]+)]/i", $replace_with, $text);

		$replace_with = $b_strip_tags ? '' : '</a>';
		$text = preg_replace("/\[\/(link|url)]/", $replace_with, $text);

		# Now convert links that were saved as HTML
		$text = preg_replace_callback('/&lt;(a .*?)&gt;(.*?)&lt;\/a&gt;/', 'XhtmlMarkup::Link_MatchEvaluator', $text);

		return $text;
	}


	/**
	 * Used as regex parameter to convert an escaped HTML link to a real one
	 * @param string[] $matches
	 * @return string
	 */
	private static function Link_MatchEvaluator($matches)
	{
		return "<" . str_replace("&quot;", '"', $matches[1]) . ">" . $matches[2] . "</a>";
	}

	/**
	 * @return string
	 * @param string $text
	 * @param bool $b_strip_tags
	 * @desc Turn [list] and [*] tags into unordered lists
	 */
	public static function ApplyLists($text, $b_strip_tags=false)
	{
		# Replace custom tags with HTML
		$replace_with = $b_strip_tags ? '' : "<\\1ul>";
		$text = preg_replace("/\[(\/?)list]\n?\n?/i", $replace_with, $text);

		$replace_with = $b_strip_tags ? '' : "<li>\\2</li>";
		$text = preg_replace("/\[(\*|item)] ?([^\n]+)\n?/i", $replace_with, $text);

		# tidy some undesirable effects
		$text = str_replace('<p><br />', '<p>', $text);
		$text = str_replace('<ul><br />', '<ul>', $text);
		$text = preg_replace('/<p>\n?<ul>/', '<ul>', $text);
		$text = str_replace("</ul></p>", '</ul>', $text);
		$text = str_replace('<br /></li>', '</li>', $text);

		# Convert escaped HTML
		$replace_with = $b_strip_tags ? "" : "<$1$2>";
		$text = preg_replace('/&lt;(\/?)(ul|ol|li)&gt;/', $replace_with, $text);

		return $text;
	}

	/**
	 * @return string
	 * @param string $s_text
	 * @param string $s_client_graphics_folder
	 * @param string $s_server_graphics_folder
	 * @param bool $b_remove_images
	 * @desc Turn [image] tags into images
	 */
	public static function ApplyImages($s_text, $s_client_graphics_folder, $s_server_graphics_folder, $b_remove_images=0)
	{
		$s_pattern_open_tag_start = "\[(captionimage|image)=";
		$s_pattern_image_filename = "([^\n<> ,:;?*\"]+)";
		$s_pattern_style_delimiter = ',?';
		$s_pattern_style = '([a-zA-Z0-9_-]*)';
		$s_pattern_open_tag_end = "]";
		$s_pattern_alt_text = "([^\n[]+)";
		$s_pattern_close_tag = "\[\/(captionimage|image)]";
		$s_pattern = '/' . $s_pattern_open_tag_start . $s_pattern_image_filename . $s_pattern_style_delimiter . $s_pattern_style . $s_pattern_open_tag_end . $s_pattern_alt_text . $s_pattern_close_tag . '/i';

		if ($b_remove_images)
		{
			$s_text = preg_replace($s_pattern, '', $s_text);
		}
		else
		{
			$i_count = 0;
			$i_max_images = 30; // shouldn't be infinite loops anyway, but just in case...
			do
			{
				unset($a_image_tag);
				preg_match($s_pattern, $s_text, $a_image_tag);
				if (is_array($a_image_tag))
				{
					if (!isset($a_image_tag[2])) $a_image_tag[2] = '';
					$s_image_server = $s_server_graphics_folder . $a_image_tag[2];
					$s_image_client = $s_client_graphics_folder . $a_image_tag[2];
					if(file_exists($s_image_server) and !is_dir($s_image_server))
					{
						$s_image_details = getimagesize($s_image_server);
						$s_new_tag = '<img src="' .$s_image_client . '" ' . $s_image_details[3];
						if (!isset($a_image_tag[3])) $a_image_tag[3] = '';
						if ($a_image_tag[3]) $s_new_tag .= ' class="' . $a_image_tag[3] . '"';
						if (!isset($a_image_tag[4])) $a_image_tag[4] = '';
						$s_new_tag .= ' alt="' . $a_image_tag[4] . '" />';
						if (isset($a_image_tag[1]) and $a_image_tag[1] == 'captionimage')
						{
							$s_new_tag = '<div class="photo">' . $s_new_tag . '</div><div class="photoCaption"><p>' . $a_image_tag[4] . '</p></div>';
						}
					}
					else
					{
						$s_new_tag = ''; // if image not found, remove the tag
					}

					// escape special characters and work out whether to look for a style
					if (isset($a_image_tag[4])) $a_image_tag[4] = XhtmlMarkup::EscapeSpecialChars($a_image_tag[4]);
					if (!isset($a_image_tag[2])) $a_image_tag[2] = '';
					if (!isset($a_image_tag[3])) $a_image_tag[3] = '';
					if (!isset($a_image_tag[4])) $a_image_tag[4] = '';
					if ($a_image_tag[3]) $a_image_tag[3] = ',' . $a_image_tag[3];

					// insert the new tag, taking the opportunity to strip any surrounding tags that aren't appropriate for an image
					$i_before = strlen($s_text);
					$s_text = preg_replace('/<p>' . $s_pattern_open_tag_start . $a_image_tag[2] . $a_image_tag[3] . $s_pattern_open_tag_end . $a_image_tag[4] . $s_pattern_close_tag . '<\/p>/i', $s_new_tag, $s_text);

					if (strlen($s_text) == $i_before)
					{
						// probably nothing changed, so just go for the image alone
						$s_text = preg_replace('/' . $s_pattern_open_tag_start . $a_image_tag[2] . $a_image_tag[3] . $s_pattern_open_tag_end . $a_image_tag[4] . $s_pattern_close_tag . '/i', $s_new_tag, $s_text);
					}
				}

				$i_count++;
			} while (is_array($a_image_tag) and ($i_count < $i_max_images));
		}

		return $s_text;
	}


	public static function CloseUnmatchedTags($s_text)
	{
		# note possible simple tags
		$a_simple_tags = array('strong', 'em', 'ul');

		# look for each tag
		for ($i_count = 0; $i_count < count($a_simple_tags); $i_count++)
		{
			# are there an unbalanced number?
			$i_open_tag_count = substr_count($s_text, '<' . $a_simple_tags[$i_count] . '>');
			$i_close_tag_count = substr_count($s_text, '</' . $a_simple_tags[$i_count] . '>');

			# until we have a balanced number
			while ($i_close_tag_count < $i_open_tag_count)
			{
				# get text before and including unclosed tag by reversing string and getting text after
				$s_text_including_unclosed_tag = stristr(strrev($s_text), strrev('<' . $a_simple_tags[$i_count] . '>'));
				# get text after tag by using length of text before
				$s_text_after_unclosed_tag = substr($s_text, strlen($s_text_including_unclosed_tag));
				# remove actual tag, and mirror text back to normal
				$s_text_before_unclosed_tag = substr($s_text_including_unclosed_tag, strlen($a_simple_tags[$i_count])+2);
				$s_text_before_unclosed_tag = strrev($s_text_before_unclosed_tag);
				# re-join two halves
				$s_text = $s_text_before_unclosed_tag . $s_text_after_unclosed_tag;

				$i_close_tag_count++;
			}
		}

		# note possible complex tags
		$a_complex_tags = array('a');

		# look for each tag
		for ($i_count = 0; $i_count < count($a_complex_tags); $i_count++)
		{
			# are there an unbalanced number?
			$i_open_tag_count = substr_count($s_text, '<' . $a_complex_tags[$i_count] . ' ');
			$i_close_tag_count = substr_count($s_text, '</' . $a_complex_tags[$i_count] . '>');

			# until we have a balanced number
			while ($i_close_tag_count < $i_open_tag_count)
			{
				# get text before and including start of unclosed tag by reversing string and getting text after
				$s_text_including_unclosed_tag = stristr(strrev($s_text), strrev('<' . $a_complex_tags[$i_count] . ' '));
				# get text after tag by using length of text before
				$s_text_after_unclosed_tag = substr($s_text, strlen($s_text_including_unclosed_tag));
				# remove start of unopened tag, and mirror text back to normal
				$s_text_before_unclosed_tag = substr($s_text_including_unclosed_tag, strlen($a_complex_tags[$i_count])+2);
				$s_text_before_unclosed_tag = strrev($s_text_before_unclosed_tag);
				# remove remainder of unclosed tag
				$s_text_after_unclosed_tag = substr($s_text_after_unclosed_tag, strpos($s_text_after_unclosed_tag, '>')+1);
				# re-join two halves
				$s_text = $s_text_before_unclosed_tag . $s_text_after_unclosed_tag;

				$i_close_tag_count++;
			}
		}

		return $s_text;
	}

	# used by public static functions above to run regexp correctly
	public static function EscapeSpecialChars($s_text)
	{
		$s_text = str_replace(".", "\.", $s_text);
		$s_text = str_replace("*", "\*", $s_text);
		$s_text = str_replace("?", "\?", $s_text);
		$s_text = str_replace("+", "\+", $s_text);
		$s_text = str_replace("[", "\[", $s_text);
		$s_text = str_replace("]", "\]", $s_text);
		$s_text = str_replace("{", "\{", $s_text);
		$s_text = str_replace("}", "\}", $s_text);
		$s_text = str_replace("(", "\(", $s_text);
		$s_text = str_replace(")", "\)", $s_text);
		$s_text = str_replace("^", "\^", $s_text);
		$s_text = str_replace("$", "\$", $s_text);
		$s_text = str_replace("|", "\|", $s_text);
		$s_text = str_replace("\\", "\\", $s_text);

		return $s_text;
	}
}
?>
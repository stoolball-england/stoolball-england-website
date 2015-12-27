<?php
require_once('xhtml/xhtml-element.class.php');
require_once('text/bad-language-filter.class.php');

class SubscriptionGrid extends XhtmlElement
{
	var $o_settings;
	var $a_subs;
	var $o_categories;

	function SubscriptionGrid(SiteSettings $o_settings, $a_subs, CategoryCollection $o_categories)
	{
		parent::XhtmlElement('div');
		$this->o_settings = $o_settings;
		$this->a_subs = $a_subs;
		$this->o_categories = $o_categories;
	}

	function OnPreRender()
	{
		# list subscriptions to pages (which may not yet be topics)
		$this->SetCssClass('subscriptions');

		if (is_array($this->a_subs) and count($this->a_subs))
		{
			# build table
			$o_table = new XhtmlElement('table');
			
			# build header row
			$o_row = new XhtmlElement('tr');

			$o_row->AddControl(new XhtmlElement('th', 'What you subscribed to'));
			$o_row->AddControl(new XhtmlElement('th', 'Date subscribed'));
			$o_row->AddControl(new XhtmlElement('th', 'Unsubscribe'));

			$o_thead = new XhtmlElement('thead', $o_row);
			$o_table->AddControl($o_thead);

			# build table body
			$o_tbody = new XhtmlElement('tbody');

			foreach($this->a_subs as $o_sub)
			{
				/* @var $o_item Category */

				$o_item = null;

				# build table row for each subscription
				if ($o_sub->GetType() == ContentType::FORUM)
				{
					# category link and date
					$o_item = $this->o_categories->GetById($o_sub->GetSubscribedItemId());
                    $s_title_method = 'GetName';
				}
				else if ($o_sub->GetType() == ContentType::STOOLBALL_MATCH)
				{
					$o_item = new Match($this->o_settings);
					$o_item->SetShortUrl($o_sub->GetSubscribedItemUrl());
				}

				if (is_object($o_item))
				{
					$o_row = new XhtmlElement('tr');

                    if ($o_item instanceof Category) {
                        $o_link = $o_sub->GetTitle();
                    } else {
    					$o_link = new XhtmlElement('a', Html::Encode($o_sub->GetTitle()));
    					$o_link->AddAttribute('href', $o_item->GetNavigateUrl());
                    }

					$o_td_item = new XhtmlElement('td', $o_link);
					if ($o_sub->GetContentDate())
					{
						$o_qualifier = new XhtmlElement('span', ' on ' . Html::Encode($o_sub->GetContentDate()));
						$o_qualifier->SetCssClass('subscriptionQualifier');
						$o_td_item->AddControl($o_qualifier);
					}

					$o_row->AddControl($o_td_item);

					# admin cells
					$o_row->AddControl($this->GetSubscribeDateCell($o_sub));
					$o_row->AddControl($this->GetActionCell($o_sub));

					$o_tbody->AddControl($o_row);
					unset($o_item);
				}
			}


			$o_table->AddControl($o_tbody);

			$this->AddControl($o_table);
		}
		else
		{
			$o_p = new XhtmlElement('p', 'You have not subscribed to any email alerts.');
			$o_p->SetCssClass('subscriptionNone');
			$this->AddControl($o_p);
		}
	}


	function GetSubscribeDateCell($o_sub)
	{
		$o_td = new XhtmlElement('td', Html::Encode(ucfirst(Date::BritishDate($o_sub->GetSubscribeDate()))));
		$o_td->SetCssClass('date');

		return $o_td;
	}

	function GetActionCell($o_sub)
	{
		# delete link
		$o_link = new XhtmlElement('a', 'Unsubscribe');
		$o_link->AddAttribute('href', $_SERVER['PHP_SELF'] . '?delete=' . $o_sub->GetSubscribedItemId() . '&amp;type=' . $o_sub->GetType());
		$o_link->AddAttribute('onclick', "return confirm('Unsubscribe from \'" . addslashes($o_sub->GetTitle()) . "\'" . '?\n\nAre you sure?\');');

		# create cell
		$o_td = new XhtmlElement('td', $o_link);
		$o_td->SetCssClass('action');

		return $o_td;
	}

}
?>
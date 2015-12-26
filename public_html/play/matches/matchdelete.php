<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('stoolball/match-manager.class.php');

class CurrentPage extends StoolballPage
{
	/**
	 * The match being deleted
	 *
	 * @var Match
	 */
	private $match;
	/**
	 * Match manager
	 *
	 * @var MatchManager
	 */
	private $manager;

	private $b_deleted = false;

	private $match_or_tournament = 'match';

	function OnPageInit()
	{
		# new data manager
		$this->manager = new MatchManager($this->GetSettings(), $this->GetDataConnection());

		parent::OnPageInit();
	}

	function OnPostback()
	{
		# Get the match info and store it
		$i_id = $this->manager->GetItemId($this->match);
		$this->manager->ReadByMatchId(array($i_id));
		$this->match = $this->manager->GetFirst();
		if (!$this->match instanceof Match)
		{
			# This can be the case if the back button is used to go back to the "match has been deleted" page.
			$this->b_deleted = true;
			return;
		}

		# Check whether cancel was clicked
		if (isset($_POST['cancel']))
		{
			$this->Redirect($this->match->GetNavigateUrl());
		}

		# Check whether delete was clicked
		if (isset($_POST['delete']))
		{
			# Check again that the requester has permission to delete this match
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES) or AuthenticationManager::GetUser()->GetId() == $this->match->GetAddedBy()->GetId());

			if ($has_permission)
			{
				# Delete the match
				$this->manager->DeleteMatch(array($i_id));

                # Remove match, and all dependent tournament matches, from search results
                require_once ("search/lucene-search.class.php");
                $search = new LuceneSearch();
                foreach ($this->match->GetMatchesInTournament() as $tournament_match) 
                {
                    $search->DeleteDocumentById("match" . $tournament_match->GetId());    
                }
                $search->DeleteDocumentById("match" . $this->match->GetId());
                $search->CommitChanges();

				require_once('stoolball/data-change-notifier.class.php');
				$notifier = new DataChangeNotifier($this->GetSettings());
				$notifier->MatchUpdated($this->match, AuthenticationManager::GetUser(), false, true);

				# Update 'next 5 matches'
				$this->manager->ReadNext();
				$this->a_next_matches = $this->manager->GetItems();


				# Note success
				$this->b_deleted = true;
			}
		}
	}

	function OnLoadPageData()
	{
		# get match
		if (!is_object($this->match))
		{
			$i_id = $this->manager->GetItemId($this->match);
			if ($i_id)
			{
				$this->manager->ReadByMatchId(array($i_id));
				$this->match = $this->manager->GetFirst();
			}
		}

		# tidy up
		unset($this->manager);

		if (is_object($this->match) and $this->match->GetMatchType() == MatchType::TOURNAMENT) $this->match_or_tournament = 'tournament';
	}

	function OnPrePageLoad()
	{
		# set page title
		if ($this->match instanceof Match)
		{
			$this->SetPageTitle('Delete ' . $this->match_or_tournament . ': ' . $this->match->GetTitle());
			$this->SetContentConstraint(StoolballPage::ConstrainColumns());
		}
		else
		{
			$this->SetPageTitle(ucfirst($this->match_or_tournament) . ' already deleted');
		}
	}

	function OnPageLoad()
	{
		if (!$this->match instanceof Match)
		{
			echo new XhtmlElement('h1', ucfirst($this->match_or_tournament) . ' already deleted');
			echo new XhtmlElement('p', "The " . $this->match_or_tournament . " you're trying to delete does not exist or has already been deleted.");
			return ;
		}

		echo new XhtmlElement('h1', 'Delete ' . $this->match_or_tournament . ': <cite>' . htmlentities($this->match->GetTitle(), ENT_QUOTES, "UTF-8", false) . '</cite>');

		if ($this->b_deleted)
		{
			?>
			<p>The <?php echo $this->match_or_tournament; ?> has been deleted.</p>
			<?php
			if ($this->match->GetTournament() instanceof Match)
			{
				echo '<p><a href="' . htmlentities($this->match->GetTournament()->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">Go to ' . htmlentities($this->match->GetTournament()->GetTitle(), ENT_QUOTES, "UTF-8", false) . '</a></p>';
			}
			else if ($this->match->Seasons()->GetCount())
			{
				foreach ($this->match->Seasons() as $season)
				{
					echo '<p><a href="' . htmlentities($season->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) . '">Go to ' . htmlentities($season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false) . '</a></p>';
				}
			}
			else
			{
				echo '<p><a href="/matches/">View all matches</a></p>';
			}
		}
		else
		{
			$has_permission = (AuthenticationManager::GetUser()->Permissions()->HasPermission(PermissionType::MANAGE_MATCHES) or AuthenticationManager::GetUser()->GetId() == $this->match->GetAddedBy()->GetId());
			if ($has_permission)
			{
				$s_detail = 'This is a ' . MatchType::Text($this->match->GetMatchType());

				$s_detail .= $this->match->GetIsStartTimeKnown() ? ' starting at ' : ' on ';
				$s_detail .= $this->match->GetStartTimeFormatted() . '. ';

				$s_context = '';
				if ($this->match->GetTournament() instanceof Match)
				{
					$s_context = "It's in the " . $this->match->GetTournament()->GetTitle();
				}

				if ($this->match->Seasons()->GetCount())
				{
					$season = $this->match->Seasons()->GetFirst();
					$b_the = !(stristr($season->GetCompetitionName(), 'the ') === 0);
					$s_context .= ($s_context) ? ', in ' : 'It\'s in ';
					$s_context .=  ($b_the ? 'the ' : '');
					if ($this->match->Seasons()->GetCount() == 1)
					{
						$s_context .= $season->GetCompetitionName() . '.';
					}
					else
					{
						$s_context .= 'following seasons: ';
					}
				}
				$s_detail .= $s_context;

				echo new XhtmlElement('p', htmlentities($s_detail, ENT_QUOTES, "UTF-8", false));

				if ($this->match->Seasons()->GetCount() > 1)
				{
					$seasons = new XhtmlElement('ul');
					foreach ($this->match->Seasons() as $season)
					{
						$seasons->AddControl(new XhtmlElement('li', htmlentities($season->GetCompetitionName(), ENT_QUOTES, "UTF-8", false)));
					}
					echo $seasons;
				}

				if ($this->match->GetMatchType() == MatchType::TOURNAMENT)
				{
					?>
					<p>Deleting a tournament cannot be undone.</p>
					<?php
				}
				else
				{
					?>
					<p>Deleting a match cannot be undone. The match will be removed from all league tables and statistics.</p>
					<?php
				}
				?>
				<p>Are you sure you want to delete this <?php echo $this->match_or_tournament; ?>?</p>
				<form action="<?php echo htmlentities($this->match->GetDeleteNavigateUrl(), ENT_QUOTES, "UTF-8", false) ?>" method="post" class="deleteButtons">
				<div>
				<input type="submit" value="Delete <?php echo $this->match_or_tournament; ?>" name="delete" />
				<input type="submit" value="Cancel" name="cancel" />
				</div>
				</form>
				<?php

				$this->AddSeparator();
				require_once('stoolball/user-edit-panel.class.php');
				$panel = new UserEditPanel($this->GetSettings(), 'this ' . $this->match_or_tournament);

				$panel->AddLink('view this ' . $this->match_or_tournament, $this->match->GetNavigateUrl());
				$panel->AddLink('edit this ' . $this->match_or_tournament, $this->match->GetEditNavigateUrl());

				echo $panel;
				}
				else
				{
				?>
				<p>Sorry, you can't delete a <?php echo $this->match_or_tournament; ?> unless you added it.</p>
				<p><a href="<?php echo htmlentities($this->match->GetNavigateUrl(), ENT_QUOTES, "UTF-8", false) ?>">Go back to <?php echo $this->match_or_tournament; ?></a></p>
				<?php
				}
			}
		}

	}
	new CurrentPage(new StoolballSettings(), PermissionType::DELETE_MATCH, false);
?>
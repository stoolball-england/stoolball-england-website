<?php
require_once('data/data-edit-control.class.php');
require_once('xhtml/tables/xhtml-table.class.php');

/**
 * Base class for editors used to manage many-to-many relationships
 *
 * TODO:
 * Basically works, but is very complicated! BACK UP BEFORE ANY FURTHER CHANGES!!!
 * 
 * Simplify it by DataObjects() not being the objects themselves, but something representing their data and info 
 * about it like the row number, whether the row's valid etc. Would be much easier to pass that info around with the
 * data object rather than trying to reconcile it at different stages in the lifecycle. Perhaps a DataItem class with
 * a DataObject property.
 * 
 * Wrote JavaScript support which works and really improves ease of use, but server-side code can't always track its changes. 
 * Tracking row numbers with data items would probably help with that.
 * 
 */
abstract class RelatedItemEditor extends DataEditControl
{
	/**
	 * Collection of data objects being edited
	 *
	 * @var Collection
	 */
	private $data_objects;

	/**
	 * The parent editor which is in control of the editing session
	 *
	 * @var DataEditControl
	 */
	private $controlling_editor;

	/**
	 * Table of data being edited
	 *
	 * @var XhtmlTable
	 */
	private $table;

	/**
	 * Tracks whether this request came from clicking a 'delete' button in this control
	 *
	 * @var bool
	 */
	private $b_delete_clicked = false;

	/**
	 * Tracks whether a new item is being added, either using 'add' button or controlling editor's 'save' button
	 *
	 * @var bool
	 */
	private $b_adding_item = false;

	/**
	 * Tracks the number of the row deleted in this request, if any
	 *
	 * @var int
	 */
	private $i_row_deleted;

	/**
	 * Tracks the number assigned to the row added in this request, if any
	 * 
	 * @var int
	 */
	private $i_row_added;

	/**
	 * Name of method used to get an id from the data object
	 *
	 * @var string
	 */
	private $s_id_method;

	/**
	 * Name of method used to get a last modified date from the data object
	 *
	 * @var string
	 */
	private $s_date_modified_method;

	/**
	 * Accessor method that returns a property to sort by
	 *
	 * @var string
	 */
	private $s_sort_method;

	/**
	 * The minimum number of items that must be selected
	 *
	 * @var int
	 */
	private $i_min_items = 0;

	/**
	 * Temporarily store the data object currently being processed
	 *
	 * @var mixed
	 */
	private $current_data_object;

	/**
	 * Temporarily store the increment number used to identify controls on the row being processed
	 *
	 * @var int
	 */
	private $i_current_row_identifier;

	/**
	 * Tracks the increment numbers of the rows that were posted
	 *
	 * @var int[]
	 */
	private $a_rows_posted;

	/**
	 * Marker text used to divide values in the same field
	 *
	 */
	const VALUE_DIVIDER = '###';

	/**
	 * Creates a RelatedItemEditor
	 *
	 * @param SiteSettings $settings
	 * @param DataEditControl $controlling_editor
	 * @param string $s_id
	 * @param string $s_title
	 * @param string $a_column_headings
	 * @param string $csrf_token
	 */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title, $a_column_headings, $csrf_token)
	{
		# Set up this control
		parent::__construct($settings, $csrf_token, false);
		$this->SetXhtmlId($s_id);
		$this->SetNamingPrefix($controlling_editor->GetXhtmlId() . $s_id);
		$this->SetShowValidationErrors(false);
		$this->AddCssClass('relatedItemEditor');
		$this->AddCssClass(get_class($this));

		# Set up the editing table
		$this->table = new XhtmlTable();
		$this->table->SetCaption($s_title);
		$this->table->SetColumnGroupSizes(array(count($a_column_headings),1));
		$colgroups = $this->table->GetColumnGroups();
		$colgroups[count($colgroups)-1]->SetCssClass('action');
		$a_column_headings[] = 'Action';
		$o_header = new XhtmlRow($a_column_headings);
		$o_header->SetIsHeader(true);
		$this->table->AddRow($o_header);

		# Initialise variables
		$this->controlling_editor = $controlling_editor;
		$this->data_objects = new Collection();
		$this->a_rows_posted = array();
	}

	/**
	 * Sets the minimum number of items which must be added using this control
	 *
	 * @param int $i_minimum
	 */
	public function SetMinimumItems($i_minimum)
	{
		$i_minimum = (int)$i_minimum;
		if ($i_minimum > -1) $this->i_min_items = $i_minimum;
	}

	/**
	 * Gets the minimum number of items which must be added using this control
	 *
	 * @return int
	 */
	public function GetMinimumItems()
	{
		return $this->i_min_items;
	}

	/**
	 * Gets the data objects being edited
	 *
	 * @return Collection
	 */
	public function DataObjects()
	{
		$this->EnsurePostedDataObject();
		return $this->data_objects;
	}

	/**
	* @return Collection
	* @desc Gets the data objects being edited
	*/
	public function GetDataObject()
	{
		return $this->DataObjects();
	}

	/**
	 * Sets the names of methods used to access common properties of the data objects being edited
	 *
	 * @param string $s_id_method
	 * @param string $s_date_modified_method
	 * @param string $s_sort_method
	 */
	protected function SetDataObjectMethods($s_id_method='', $s_date_modified_method='', $s_sort_method='')
	{
		$this->s_id_method = (string)$s_id_method;
		$this->s_date_modified_method = (string)$s_date_modified_method;
		$this->s_sort_method = (string)$s_sort_method;
	}

	/**
	 * Gets the id of an object being edited using its registered id method
	 *
	 * @param object $object
	 * @return int
	 */
	protected function GetObjectId($object)
	{
		$id_method = $this->s_id_method;
		return $object->$id_method;
	}

	/**
	 * Allows a child control to register a button as one which alters the state of the control, rather than initiating a save
	 *
	 * @param string $s_id
	 */
	public function RegisterInternalButton($s_id)
	{
		parent::RegisterInternalButton($s_id);
		return $this->controlling_editor->RegisterInternalButton($s_id);
	}

	/**
	 * Allows a child control to remove a button from the list of those which alter the state of the control, rather than initiating a save
	 *
	 * @param string $s_id
	 */
	public function RemoveInternalButon($s_id)
	{
		parent::RemoveInternalButon($s_id);
		return $this->controlling_editor->RemoveInternalButton($s_id);
	}

	/**
	 * Gets whether the current request is the internal postback of another control
	 *
	 * @return bool
	 */
	protected function IsUnrelatedInternalPostback()
	{
		return ($this->controlling_editor->IsInternalPostback() and !$this->IsInternalPostback());
	}

	/**
	 * Gets whether this request came from clicking a delete button in this control
	 *
	 * @return bool
	 */
	protected function DeleteClicked()
	{
		return $this->b_delete_clicked;
	}

	/**
	 * Gets whether this request came from clicking the delete button for the specified item
	 *
	 * @param int $i_counter
	 * @return bool
	 */
	private function ItemDeleting($i_counter)
	{
		# A related item is identified by a counter.
		# Check whether one of those is being deleted.
		$s_key = $this->GetNamingPrefix() . 'DeleteRelated' . $i_counter;
		if (isset($_POST[$s_key]))
		{
			# Track that this is a delete request
			$this->b_delete_clicked = true;

			# Remember which item was deleted
			$this->i_row_deleted = $i_counter;

			#$this->IgnorePostedItem($i_counter);

			# Return that this item is being deleted
			return true;
		}
		return false;
	}

	/**
	 * Gets whether this request came from clicking the add button in this control
	 *
	 * @return bool
	 */
	protected function AddClicked()
	{
		return isset($_POST[$this->GetNamingPrefix() . 'AddRelated']);
	}


	/**
	 * Tracks whether a new item is being added, either using 'add' button or controlling editor's 'save' button
	 *
	 * @var bool
	 */
	protected function Adding()
	{
		return $this->b_adding_item;
	}

	/**
	* @return void
	* @desc Re-build from data posted by this control the data objects this control is editing
	*/
	protected function BuildPostedDataObject()
	{
		# Rows are numbered incrementally, but if JavaScript delete was used some may be missing so we can't just loop.
		# The internal buttons array should have a reference to the delete button for each row, so get the delete buttons
		# from it and take the increment number off the end of the id to get the ones used.
		$s_key = $this->GetNamingPrefix() . 'InternalButtons';
		$a_delete_buttons = array();
		if (isset($_POST[$s_key]))
		{
			$a_buttons = explode('; ', $_POST[$s_key]);
			$s_key = $this->GetNamingPrefix() . 'DeleteRelated';
			foreach ($a_buttons as $s_button)
			{
				if (strpos($s_button, $s_key) === 0) $a_delete_buttons[] = (int)substr($s_button, strlen($s_key));
			}
		}

		# Now loop through the increment numbers got from the internal buttons array, and remember
		# just those rows that were actually posted. Store that in a private field for use here and elsewhere in the class.
		$i = 0;
		do
		{
			$i++;
			$s_key = $this->GetNamingPrefix() . 'RelatedId' . $i;
			$b_rowfound = isset($_POST[$s_key]);

			if ($b_rowfound)
			{
				$this->a_rows_posted[] = $i;
			}
			else if (in_array($i, $a_delete_buttons, true))
			{
				# If this increment number was used, but was deleted before this postback, just carry on
				continue;
			}
			else
			{
				break;
			}
		} while (true);

		# Rebuild items already added to the collection
		foreach ($this->a_rows_posted as $i)
		{
			$i_id = null;
			$s_key = $this->GetNamingPrefix() . 'RelatedId' . $i;
			if (strlen($_POST[$s_key]) and is_numeric($_POST[$s_key])) $i_id = (int)$_POST[$s_key];

			if (!$this->ItemDeleting($i)) $this->BuildPostedItem($i, $i_id);
		}

		# Add new item to the collection if "add" button of this editor, or if overall "save" on controlling editor, is clicked
		if ($this->AddClicked() or !$this->controlling_editor->IsInternalPostback())
		{
			$i_count_before = $this->DataObjects()->GetCount();
			$this->BuildPostedItem();
			$i_count_after = $this->DataObjects()->GetCount();
			if ($i_count_after > $i_count_before)
			{
				$this->b_adding_item = true;
				$this->i_row_added = count($this->a_rows_posted) ? max($this->a_rows_posted)+1 : 1; # assign a new increment to the row

				# Remove: use the fact that it's shorter to exclude new row from sort, and keep it at the end
				# $this->a_rows_posted[] = $this->i_row_added; # make sure row count matches that of DataObjects array, so that multisort can work
			}
		}

		# Delete reference to the item being deleted
		if ($this->i_row_deleted) $this->IgnorePostedItem($this->i_row_deleted);
	}

	/**
	 * Re-build from data posted by this control a single data object which this control is editing
	 *
	 * @param int $i_counter
	 * @param int $i_id
	 */
	protected abstract function BuildPostedItem($i_counter=null, $i_id=null);

	/**
	 * Gets the previous modified date, or resets it (UTC) if the posted item contains new or changed data
	 *
	 * @param int $i_counter
	 * @param object $data_object
	 * @return int
	 */
	protected function BuildPostedItemModifiedDate($i_counter, $data_object)
	{
		$b_changed = $this->HasItemChanged($i_counter, $data_object);
		$s_key = $this->GetNamingPrefix() . 'RelatedUpdated' . $i_counter;
		return (!$b_changed and isset($_POST[$s_key]) and is_numeric($_POST[$s_key])) ? $_POST[$s_key] : gmdate('U');
	}

	/**
	 * Allows a child class to ignore a row, eg if it is blank
	 *
	 * @param int $i_counter
	 */
	protected function IgnorePostedItem($i_counter)
	{
		if (is_null($i_counter)) return;
		$i_key = array_search($i_counter, $this->a_rows_posted, true);
		if (!($i_key === false))
		{
			unset($this->a_rows_posted[$i_key]);
		}
	}

	/**
	 * Determines whether the posted item contains new or changed data
	 *
	 * @param int $i_counter
	 * @param object $data_object
	 * @return bool
	 */
	private function HasItemChanged($i_counter, $data_object)
	{
		$s_key = $this->GetNamingPrefix() . 'Check' . $i_counter;
		$b_changed = empty($_POST[$s_key]); # should confirm a new record has "changed"
		if (!$b_changed)
		{
			# checks whether an existing record has changed, based on a hash of its properties implemented by the child class
			$s_check = $this->GetDataObjectHash($data_object);
			$b_changed = ($_POST[$s_key] != $s_check);
		}
		return $b_changed;
	}

	/**
	 * Gets a hash of the specified values
	 *
	 * @param string[] $a_values
	 * @return string
	 */
	protected function GenerateHash($a_values)
	{
		if (is_array($a_values))
		{
			return md5(implode($a_values));
		}
		else
		{
			throw new Exception('No values to hash');
		}
	}

	/**
	 * Gets a hash of the specified data object
	 *
	 * @param object $data_object
	 */
	protected abstract function GetDataObjectHash($data_object);


	/**
	 * Gets a sorted array of selected values to guide an array_multisort
	 *
	 */
	protected function GetSortArray()
	{
		if (!$this->s_sort_method) $this->s_sort_method = '__toString';
		$a_sort = array();
		$s_sort_method = $this->s_sort_method;
		foreach ($this->DataObjects() as $data_object) $a_sort[] = $data_object->$s_sort_method();
		return $a_sort;
	}

	/**
	 * Create and populate the table rows used to edit the data objects
	 *
	 */
	public function CreateControls()
	{
		# Sort current items
		/*		$a_sort = $this->GetSortArray();
		if (is_array($a_sort))
		{
		$a_data = $this->data_objects->GetItems();
		if ($this->IsPostback())
		{
		# Make sure the 'new' row remains at the end
		# TODO: This behaviour is not quite right as it should only happen if the 'new' row was not actually added, eg its invalid
		# At the moment new additions are not sorted immediately.
		if (count($this->a_rows_posted) == $this->data_objects->GetCount()-1)
		{
		$new_item = array_pop($a_data);
		array_pop($a_sort);
		array_multisort($a_sort, $a_data, $this->a_rows_posted);
		$a_data[] = $new_item;
		}
		else
		{

		array_multisort($a_sort, $a_data, $this->a_rows_posted);
		}
		}
		else
		{
		array_multisort($a_sort, $a_data);
		}

		$this->data_objects->SetItems($a_data);
		}
		*/
		# Create row for each currently-related item
		$i_row_count = 0;
		$i_total_rows = $this->DataObjects()->GetCount();
		foreach ($this->DataObjects() as $data_object)
		{
			# If add row and not valid, skip and repopulate it later
			if (($i_row_count+1) == $i_total_rows and $this->Adding() and !$this->IsValid()) continue;

			# Remember which data object we're dealing with
			$this->current_data_object = $data_object;

			# Work out increment by which row will be identified, if postback preserve existing,
			# otherwise it's sequential: just add one to be 1-based rather than 0-based index.
			
			# Check for the InternalButtons field, not because we care about it but because it proves that this control
			# was present before the page was posted back. This is relevant in a wizard where a post on one page shows the
			# next page of the wizard including this control, newly added.
			$this->i_current_row_identifier = $i_row_count+1;
			if ($this->IsPostback() and isset($_POST[$this->GetNamingPrefix() . 'InternalButtons']))
			{
				if (isset($this->a_rows_posted[$i_row_count]))
				{
					$this->i_current_row_identifier = $this->a_rows_posted[$i_row_count];
				}
				else
				{
					# $this->i_row_added situation here should account for new data being added from the 'new' rows

					$i_row_count++;
					$this->i_current_row_identifier = (isset($this->a_rows_posted[$i_row_count])) ? $this->a_rows_posted[$i_row_count] : $this->i_row_added;
				}
			}

			$i_row_count++;

			# Ready to add the item
			$this->AddItemToTable($data_object, $this->i_current_row_identifier, $i_total_rows);
		}

		# Create a row to add a new item
		$this->current_data_object = null;
		$this->AddItemToTable();

		# Add the table to the controls collection
		$this->AddControl($this->table);
	}

	/**
	 * Create a table row to add or edit a single data object
	 *
	 * @param object $data_object
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 */
	protected abstract function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null);

	/**
	 * Adds populated controls as a new table row
	 *
	 * @param XhtmlElement[] $a_controls
	 * @param bool $b_enabled
	 * @return XhtmlRow
	 */
	protected function AddRowToTable($a_controls, $b_enabled=true)
	{
		# Add action button to controls for table
		$button = new XhtmlElement('input');
		if (!$b_enabled) $button->AddAttribute('disabled', 'disabled');
		$last_cell_data = new Placeholder($button);

		$s_id = '';
		if (!is_null($this->current_data_object))
		{
			# Get methods for data objects
			$s_id_method = $this->s_id_method;
			$s_date_modified_method = $this->s_date_modified_method;

			# Update button
			$button->AddAttribute('value', 'Delete');
			$button->SetXhtmlId($this->GetNamingPrefix() . 'DeleteRelated' . $this->i_current_row_identifier);

			# Hidden - id of related item
			$s_id = $this->current_data_object->$s_id_method();
			$s_id = $s_id ? $s_id : $this->i_current_row_identifier;
			$id_box = new TextBox($this->GetNamingPrefix() . 'RelatedId' . $this->i_current_row_identifier, $s_id);
			$id_box->SetMode(TextBoxMode::Hidden());
			$last_cell_data->AddControl($id_box);

			# Hidden - when was relationship to item updated?
			$b_track_modified_date = ($s_date_modified_method and method_exists($this->current_data_object, $s_date_modified_method));
			if ($b_track_modified_date)
			{
				$updated_box = new TextBox($this->GetNamingPrefix() . 'RelatedUpdated' . $this->i_current_row_identifier, $this->current_data_object->$s_date_modified_method());
				$updated_box->SetMode(TextBoxMode::Hidden());
				$last_cell_data->AddControl($updated_box);
			}

			# Hidden - check whether item updated
			$check_box = new TextBox($this->GetNamingPrefix() . 'Check' . $this->i_current_row_identifier, $this->GetDataObjectHash($this->current_data_object));
			$check_box->SetMode(TextBoxMode::Hidden());
			$last_cell_data->AddControl($check_box);
		}
		else
		{
			$button->AddAttribute('value', 'Add');
			$button->SetXhtmlId($this->GetNamingPrefix() . 'AddRelated');
		}
		$button->AddAttribute('type', 'submit');
		$button->SetEmpty(true);
		$button->AddAttribute('name', $button->GetXhtmlId());
		$this->RegisterInternalButton($button->GetXhtmlId());

		# Create table row and add to table
		$a_controls[] = $last_cell_data;
		$row = new XhtmlRow($a_controls);
		if (!$b_enabled) $row->SetCssClass('unavailable');
		$this->table->AddRow($row);
		return $row;
	}

	/**
	 * Creates, and re-populates on postback, a textbox to manage one property of a related data item
	 *
	 * @param string $s_validated_value
	 * @param string $s_name
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 * @param bool $b_required
	 * @return TextBox
	 */
	protected function CreateTextBox($s_validated_value, $s_name, $i_row_count, $i_total_rows, $b_required=false)
	{
		# Establish current status
		$b_is_add_row = is_null($i_row_count);
		$b_repopulate_add_row = (!$this->IsValid() or $this->DeleteClicked() or ($this->IsUnrelatedInternalPostback()));

		# Create text box
		$textbox = new TextBox($this->GetNamingPrefix() . $s_name . $i_row_count);
		if ($b_required) $textbox->AddAttribute('class', 'required');

		# Repopulate the previous value

		# If this is the row used to add a new related item...
		if ($b_is_add_row)
		{
            # ...if we repopulate, it'll be with the exact value posted,
			# as validation has either failed or not occurred
			if ($b_repopulate_add_row and isset($_POST[$textbox->GetXhtmlId()]))
			{
				$textbox->SetText($_POST[$textbox->GetXhtmlId()]);
			}
		}
		else
		{
    		if ($this->IsValid())
			{
				if ($this->AddClicked() and $i_row_count == $this->i_row_added)
				{
					# If a new row was posted, is valid, and is now displayed here, need to get the posted value from the add row
					$s_textbox_in_add_row = substr($textbox->GetXhtmlId(), 0, strlen($textbox->GetXhtmlId())-strlen($i_row_count));
					$textbox->SetText($_POST[$s_textbox_in_add_row]);
				}
				//if ($this->DeleteClicked() or ($this->AddClicked() and $i_row_count != $this->i_row_added))
				else
				{
					# Even though the editor as a whole is valid, this row wasn't validated
					# so just restore the previous value
					if (isset($_POST[$textbox->GetXhtmlId()]))
					{
						$textbox->SetText($_POST[$textbox->GetXhtmlId()]);
					}
					else
					{
						# Won't be in $_POST when page is first loaded
						$textbox->SetText($s_validated_value);
					}
				}
			}
			else
			{
				# Repopulate with the exact value posted, as validation has failed
				$textbox->SetText($_POST[$textbox->GetXhtmlId()]);
			}
		}

		# Return textbox so that other things can be done to it - eg add a CSS class or maxlength
		return $textbox;
	}

	/**
	 * Creates, and re-populates on postback, non-editable text with an associated id
	 *
	 * @param string $s_validated_value
	 * @param string $s_name
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 * @param bool $b_required
	 * @return TextBox
	 */
	protected function CreateNonEditableText($i_validated_id, $s_validated_value, $s_name, $i_row_count, $i_total_rows)
	{
		# Establish current status
		$b_is_add_row = is_null($i_row_count);
		$b_repopulate_add_row = (!$this->IsValid() or $this->DeleteClicked() or ($this->IsUnrelatedInternalPostback()));

		# Create text boxes
		$textbox_id = new TextBox($this->GetNamingPrefix() . $s_name . $i_row_count);
		$textbox_id->SetMode(TextBoxMode::Hidden());
		$textbox_value = new TextBox($this->GetNamingPrefix() . $s_name . 'Value' . $i_row_count);
		$textbox_value->SetMode(TextBoxMode::Hidden());

		# Repopulate the previous value

		# If this is the row used to add a new related item...
		if ($b_is_add_row)
		{
			# ...if we repopulate, it'll be with the exact value posted,
			# as validation has either failed or not occurred
			if ($b_repopulate_add_row and isset($_POST[$textbox_id->GetXhtmlId()]))
			{
				$textbox_id->SetText($_POST[$textbox_id->GetXhtmlId()]);
			}
			if ($b_repopulate_add_row and isset($_POST[$textbox_value->GetXhtmlId()]))
			{
				$textbox_value->SetText($_POST[$textbox_value->GetXhtmlId()]);
			}
		}
		else
		{
			if ($this->IsValid())
			{
				if ($this->AddClicked() and $i_row_count == $this->i_row_added)
				{
					# If a new row was posted, is valid, and is now displayed here, need to get the posted value from the add row
					$s_textbox_id_in_add_row = substr($textbox_id->GetXhtmlId(), 0, strlen($textbox_id->GetXhtmlId())-strlen($i_row_count));
					if (isset($_POST[$s_textbox_id_in_add_row])) $textbox_id->SetText($_POST[$s_textbox_id_in_add_row]);

					$s_textbox_value_in_add_row = substr($textbox_value->GetXhtmlId(), 0, strlen($textbox_value->GetXhtmlId())-strlen($i_row_count));
					if (isset($_POST[$s_textbox_value_in_add_row]))
					{
						$textbox_value->SetText($_POST[$s_textbox_value_in_add_row]);
					}
					else 
					{
						# If the add row doesn't populate the item name as well as the item id, it can be passed in as the validated value
						$textbox_value->SetText($s_validated_value);
					}
				}
				//if ($this->DeleteClicked() or ($this->AddClicked() and $i_row_count != $this->i_row_added))
				else
				{
					# Even though the editor as a whole is valid, this row wasn't validated
					# so just restore the previous value
					if (isset($_POST[$textbox_id->GetXhtmlId()]))
					{
						$textbox_id->SetText($_POST[$textbox_id->GetXhtmlId()]);
					}
					else
					{
						# Won't be in $_POST when page is first loaded
						$textbox_id->SetText($i_validated_id);
					}

					# Even though the editor as a whole is valid, this row wasn't validated
					# so just restore the previous value
					if (isset($_POST[$textbox_value->GetXhtmlId()]))
					{
						$textbox_value->SetText($_POST[$textbox_value->GetXhtmlId()]);
					}
					else
					{
						# Won't be in $_POST when page is first loaded
						$textbox_value->SetText($s_validated_value);
					}
				}
			}
			else
			{
				# Repopulate with the exact value posted, as validation has failed
				$textbox_id->SetText($_POST[$textbox_id->GetXhtmlId()]);
				$textbox_value->SetText($_POST[$textbox_value->GetXhtmlId()]);
			}
		}

		# Return textboxes so that other things can be done to them - eg add a CSS class or maxlength
		$placeholder = new Placeholder();
		$placeholder->AddControl($textbox_id);
		$placeholder->AddControl($textbox_value);
		$placeholder->AddControl($textbox_value->GetText());
		return $placeholder;
	}


	/**
	 * Creates, and re-populates on postback, a checkbox to manage one property of a related data item
	 *
	 * @param bool $b_validated_value
	 * @param string $s_name
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 * @return TextBox
	 */
	protected function CreateCheckbox($b_validated_value, $s_name, $i_row_count, $i_total_rows)
	{
		require_once('xhtml/forms/checkbox.class.php');

		# Establish current status
		$b_is_add_row = is_null($i_row_count);
		$b_repopulate_add_row = (!$this->IsValid() or $this->DeleteClicked() or ($this->IsUnrelatedInternalPostback()));

		# Create checkbox
		$checkbox = new CheckBox($this->GetNamingPrefix() . $s_name . $i_row_count, '', 1);

		# Repopulate the previous value

		# If this is the row used to add a new related item...
		if ($b_is_add_row)
		{
			# ...if we repopulate, it's because validation has either failed or not occurred
			if ($b_repopulate_add_row)
			{
				$checkbox->SetChecked(isset($_POST[$checkbox->GetXhtmlId()]));
			}
		}
		else
		{
			if ($this->IsValid())
			{
				if ($this->AddClicked() and $i_row_count == $this->i_row_added)
				{
					# If a new row was posted, is valid, and is now displayed here, need to get the posted value from the add row
					$s_checkbox_in_add_row = substr($checkbox->GetXhtmlId(), 0, strlen($checkbox->GetXhtmlId())-strlen($i_row_count));
					$checkbox->SetChecked(isset($_POST[$s_checkbox_in_add_row]));
				}
				//if ($this->DeleteClicked() or ($this->AddClicked() and $i_row_count != $this->i_row_added))
				else
				{
					# Even though the editor as a whole is valid, this row wasn't validated
					# so just restore the previous value
					if ($this->IsPostback())
					{
						$checkbox->SetChecked(isset($_POST[$checkbox->GetXhtmlId()]));
					}
					else
					{
						# Won't be in $_POST when page is first loaded
						$checkbox->SetChecked($b_validated_value);
					}
				}
			}
			else
			{
				# Repopulate with the exact value posted, as validation has failed
				$checkbox->SetChecked(isset($_POST[$checkbox->GetXhtmlId()]));
			}
		}

		# Return checkbox so that other things can be done to it - eg add a CSS class
		return $checkbox;
	}


	/**
	 * Configures, and repopulates on postback, a dropdown list to manage one property of a related data item
	 *
	 * @param XhtmlSelect $select
	 * @param string $s_validated_value
	 * @param string $s_name
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 * @param bool $b_required
	 * @param string $s_default_value
	 * @return XhtmlSelect
	 */
	protected function ConfigureSelect($select, $s_validated_value, $s_name, $i_row_count, $i_total_rows, $b_required=false, $s_default_value='')
	{
		# Establish current status
		$b_is_add_row = is_null($i_row_count);
		$b_repopulate_add_row = (!$this->IsValid() or $this->DeleteClicked() or $this->IsUnrelatedInternalPostback());
		$select->SetXhtmlId($this->GetNamingPrefix() . $s_name . $i_row_count);
		if ($b_required) $select->SetCssClass('required');

		# If this is the row used to add a new related item...
		# Add a blank option only if data is not required, or there's no obvious default
		if ($b_is_add_row and (!$b_required or !strlen($s_default_value))) $select->SetBlankFirst(true);

		# If there is a default, set it
		if ($b_is_add_row and !$this->IsPostback() and strlen($s_default_value)) $select->SelectOption($s_default_value);

		# But if it's an existing row and data is required, there's no blank option...
		if (!$b_is_add_row and !$b_required) $select->SetBlankFirst(true);

		# If this is the row used to add a new related item...
		if ($b_is_add_row)
		{
			# ...if we repopulate, it'll be with the exact value posted,
			# as validation has either failed or not occurred
			if ($b_repopulate_add_row and isset($_POST[$select->GetXhtmlId()]))
			{
				$select->SelectOption($_POST[$select->GetXhtmlId()]);
			}
		}
		else
		{
			if ($this->IsValid())
			{
				if ($this->AddClicked() and $i_row_count == $this->i_row_added)
				{
					# If a new row was posted, is valid, and is now displayed here, need to get the posted value from the add row
					$s_select_in_add_row = substr($select->GetXhtmlId(), 0, strlen($select->GetXhtmlId())-strlen($i_row_count));
					$select->SelectOption($_POST[$s_select_in_add_row]);
				}
				//if ($this->DeleteClicked() or ($this->AddClicked() and $i_row_count != $this->i_row_added))
				else
				{
					# Even though the editor as a whole is valid, this row wasn't validated
					# so just restore the previous value
					if (isset($_POST[$select->GetXhtmlId()]))
					{
						$select->SelectOption($_POST[$select->GetXhtmlId()]);
					}
					else
					{
						# Won't be in $_POST when page is first loaded
						$select->SelectOption($s_validated_value);
					}
				}
			}
			else
			{
				# Repopulate with the exact value posted, as validation has failed
				$select->SelectOption($_POST[$select->GetXhtmlId()]);
			}
		}

		# Return dropdown so that other things can be done to it - eg add a CSS class
		return $select;
	}

	/**
	* @return void
	* @desc Create DataValidator objects to validate the edit control
	*/
	public function CreateValidators()
	{
		# Validate existing rows only if 'add' or 'delete' not clicked,
		# because those buttons should only affect the row those buttons relate to.
		$i_rows_posted = count($this->a_rows_posted);
		if (!$this->controlling_editor->IsInternalPostback() and $i_rows_posted)
		{
			$i_last_item = $this->a_rows_posted[$i_rows_posted-1];
			foreach ($this->a_rows_posted as $i)
			{
				if ($i == $i_last_item and $this->Adding()) continue; # ignore last one if it's the new row

				$this->CreateValidatorsForItem($i);
			}
		}

		# Validate the new row, unless 'delete' was clicked on another row in this control,
		# because then only the deleted row should be affected, or unless any internal
		# button in another control was clicked.
		if (!$this->DeleteClicked() and !$this->IsUnrelatedInternalPostback())
		{
			$this->CreateValidatorsForItem();
		}

		# If saving, and this control doesn't have its minimum number of items, force a validation error
		$i_total_items = $this->DataObjects()->GetCount();

		if ($i_total_items < $this->i_min_items and !$this->controlling_editor->IsInternalPostback())
		{
			$force_invalid = new RequiredFieldValidator('ForceInvalid', 'Please add at least one item in \'' . strtolower($this->table->GetCaption()) . '\'');
			$force_invalid->SetValidIfNotFound(false);
			$this->AddValidator($force_invalid);
		}
	}

	/**
	 * Create validators to check a single data object
	 *
	 * @param string $s_item_suffix
	 */
	protected abstract function CreateValidatorsForItem($s_item_suffix='');
}
?>
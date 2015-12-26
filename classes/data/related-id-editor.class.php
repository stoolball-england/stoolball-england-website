<?php
require_once('data/related-item-editor.class.php');

/**
 * Aggregated editor to manage values related by a unique identifier
 *
 */
class RelatedIdEditor extends RelatedItemEditor
{
	/**
	 * Objects which might be related, indexed by their ids
	 *
	 * @var object[]
	 */
	private $a_possible_data;

	/**
	 * Ids of the selected data objects
	 *
	 * @var int[]
	 */
	private $a_data_object_ids;

	/**
	 * The name of the data object class
	 *
	 * @var string
	 */
	private $s_data_object_class;

	/**
	 * Does the data object constructor take a SiteSettings object?
	 *
	 * @var bool
	 */
	private $b_require_settings;

	/**
	 * The method name used to get an identifier from a data object
	 *
	 * @var string
	 */
	private $s_get_id_method;

	/**
	 * The method name used to set an identifier in a data object
	 *
	 * @var string
	 */
	private $s_set_id_method;

	/**
	 * The method name used to set a display name in a data object
	 *
	 * @var string
	 */
	private $s_set_display_name_method;

	/**
	 * Disable selected items rather than removing them?
	 *
	 * @var bool
	 */
	private $b_disable_selected_items = false;

	/**
	 * Creates an RelatedIdEditor
	 *
	 * @param SiteSettings $settings
	 * @param DataEditControl $controlling_editor
	 * @param string $s_id
	 * @param string $s_title
	 * @param string[] $a_column_headings
	 * @param string $s_data_object_class
	 * @param bool $b_require_settings
	 * @param string $s_get_id_method
	 * @param string $s_set_id_method
	 * @param string $s_set_display_name_method
	 */
	public function __construct(SiteSettings $settings, DataEditControl $controlling_editor, $s_id, $s_title, $a_column_headings, $s_data_object_class, $b_require_settings, $s_get_id_method, $s_set_id_method, $s_set_display_name_method)
	{
		parent::__construct($settings, $controlling_editor, $s_id, $s_title, $a_column_headings);

		# initialise varibles
		$this->a_possible_data = array();
		$this->a_data_object_ids = array();
		$this->s_data_object_class = (string)$s_data_object_class;
		$this->b_require_settings = (bool)$b_require_settings;
		$this->s_get_id_method = (string)$s_get_id_method;
		$this->s_set_id_method = (string)$s_set_id_method;
		$this->s_set_display_name_method = (string)$s_set_display_name_method;
		$this->SetDataObjectMethods($s_get_id_method);
	}


	/**
	 * Sets the data objects which might be related
	 *
	 * @param mixed[] $a_data
	 */
	public function SetPossibleDataObjects($a_data)
	{
		if (!is_array($a_data)) return;

		$s_get_id_method = $this->s_get_id_method;
		$this->a_possible_data = array();

		foreach ($a_data as $data_object)
		{
			$this->a_possible_data[$data_object->$s_get_id_method()] = $data_object;
		}
	}


	/**
	 * Sets whether to disable, rather than remove, already-selected items from the 'new' dropdown
	 *
	 * @param bool $disable
	 */
	protected function SetDisableSelected($disable)
	{
		$this->b_disable_selected_items = (bool)$disable;
	}

	/**
	 * Gets whether to disable, rather than remove, already-selected items from the 'new' dropdown
	 *
	 * @return bool
	 */
	protected function GetDisableSelected() { return $this->b_disable_selected_items; }

	/**
	 * Re-build from data posted by this control a single data object which this control is editing
	 *
	 * @param int $i_counter
	 * @param int $i_id
	 */
	protected function BuildPostedItem($i_counter=null, $i_id=null)
	{
		$a_posted = $this->GetPostedIdValue($this->s_data_object_class, $i_counter);
		if (strlen($a_posted[0]) and strlen($a_posted[1])) # Test as a string because it might be an integer value of 0 or less...
		{
			$data_object = null;
			if ($this->b_require_settings)
			{
				$data_object = new $this->s_data_object_class($this->GetSettings());
			}
			else
			{
				$data_object = new $this->s_data_object_class();
			}

			$s_id_method = $this->s_set_id_method;
			$s_display_name_method = $this->s_set_display_name_method;
			$data_object->$s_id_method($a_posted[0]);
			$data_object->$s_display_name_method($a_posted[1]);

			$this->DataObjects()->Add($data_object);
		}
	}

	/**
	 * Gets an array of an id and value posted together
	 *
	 * @param string $s_field_id
	 * @param int $i_counter
	 */
	protected function GetPostedIdValue($s_field_id, $i_counter)
	{
		# Expects to find format of:
		#
		#    numeric id[divider]string value
		#
		# but will accept format of:
		#
		#    numeric id
		#
		# or:
		#
		#    numeric id[divider]

		$s_key = $this->GetNamingPrefix() . $s_field_id . $i_counter;
		$s_posted = isset($_POST[$s_key]) ? $_POST[$s_key] : '';
		$i_id = null;
		$s_value = null;

		if ($s_posted)
		{
			$i_marker_pos = strpos($s_posted, RelatedIdEditor::VALUE_DIVIDER);
			if ($i_marker_pos)
			{
				$s_id = substr($s_posted, 0, $i_marker_pos);
				if (is_numeric($s_id)) $i_id = (int)$s_id;

				$s_value = substr($s_posted, $i_marker_pos + strlen(RelatedIdEditor::VALUE_DIVIDER));
			}
			else if (is_numeric($s_posted))
			{
				$i_id = (int)$s_posted;
			}
		}
		return array($i_id, $s_value);
	}

	/**
	 * Gets a hash of a data object
	 *
	 * @param int $data_object
	 * @return string
	 */
	protected function GetDataObjectHash($data_object)
	{
		$s_get_id_method = $this->s_get_id_method;
		return $this->GenerateHash(array($data_object->$s_get_id_method(), $data_object->__toString()));
	}

	/**
	 * Collect data object ids before building controls
	 *
	 */
	public function CreateControls()
	{
		$s_id_method = $this->s_get_id_method;
		$this->DataObjects()->ResetCounter();
		while ($this->DataObjects()->MoveNext())
		{
			$this->a_data_object_ids[] = $this->DataObjects()->GetItem()->$s_id_method();
		}
		parent::CreateControls();
	}

	/**
	 * Create a table row to add or display a data object
	 *
	 * @param object $data_object
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 */
	protected function AddItemToTable($data_object=null, $i_row_count=null, $i_total_rows=null)
	{
		$b_has_data_object = !is_null($data_object);
		$b_enable_row = true;
		$s_get_id_method = $this->s_get_id_method;

		if ($b_has_data_object)
		{
			# Display selected object
			$box = $this->CreateDisplayText($data_object->$s_get_id_method(), $this->s_data_object_class, $i_row_count, $i_total_rows, $this->a_possible_data);
		}
		else
		{
			# Row for new value

			$a_objects_to_add = array();
			$a_objects_to_disable = array();

			# Filter out items which are already selected, or make a note if they were just disabled
			foreach ($this->a_possible_data as $i_id => $obj)
			{
				# Is item already selected?
				if (in_array($i_id, $this->a_data_object_ids, true))
				{
					if ($this->GetDisableSelected())
					{
						$a_objects_to_add[$i_id] = $obj;
						$a_objects_to_disable[] = $i_id;
					}
					else continue;
				}
				else
				{
					$a_objects_to_add[$i_id] = $obj;
				}
			}

			# Add those items to dropdown
			$select = $this->CreateSelect();
			$select = $this->AddOptions($select, $a_objects_to_add, $a_objects_to_disable);
			$box = $this->ConfigureSelect($select, '', $this->s_data_object_class, $i_row_count, $i_total_rows, true);
			$box->SetCssClass('required unique');

			# If a blank value is selected, make sure the blank option is selected.
			# Otherwise one of the disabled options might be selected instead.
			if (!$box->GetSelectedValue()) $box->SelectNothing();

			if (!count($a_objects_to_add))
			{
				$box->SetEnabled(false);
				$b_enable_row = false;
			}
		}

		# Add controls to table
		$row = $this->AddRowToTable(array($box), $b_enable_row);
		$row->AddCssClass('display');
	}

	/**
	 * Creates, and re-populates on postback, text displaying one property of a related data item
	 *
	 * @param string $s_validated_value
	 * @param string $s_name
	 * @param int $i_row_count
	 * @param int $i_total_rows
	 * @param string[] $a_display_values
	 * @return Placeholder
	 */
	protected function CreateDisplayText($s_validated_value, $s_name, $i_row_count, $i_total_rows, $a_display_values=null)
	{     
		$textbox = $this->CreateTextBox($s_validated_value, $s_name, $i_row_count, $i_total_rows);

		$textbox->SetMode(TextBoxMode::Hidden());

		$container = new Placeholder($textbox);
		if (is_array($a_display_values) and array_key_exists($textbox->GetText(), $a_display_values))
		{
            # This path used on initial load request, and when controlling editor's save button is clicked when a row is being added
			# Textbox value is the key to an array of values
			$container->AddControl($a_display_values[$textbox->GetText()]->__toString());
			$textbox->SetText($textbox->GetText() . RelatedIdEditor::VALUE_DIVIDER . $a_display_values[$textbox->GetText()]->__toString());
		}
		else
		{
			# Copy textbox value, remove prepended id, and use as display text
			$s_value = $textbox->GetText();
			$i_pos = strpos($s_value, RelatedIdEditor::VALUE_DIVIDER);
			if ($i_pos) $s_value = substr($s_value, $i_pos + strlen(RelatedIdEditor::VALUE_DIVIDER));

			$container->AddControl(ucfirst($s_value));
		}
		return $container;
	}

	/**
	 * Creates the dropdown list to pick new items from
	 *
	 * @return XhtmlSelect
	 */
	protected function CreateSelect()
	{
		return new XhtmlSelect();
	}

	/**
	 * Creates an XhtmlOption from its object
	 *
	 * @param int $id
	 * @param object $object
	 * @return XhtmlOption
	 */
	protected function CreateOption($id, $object)
	{
		return new XhtmlOption(ucfirst($object->__toString()), $id . RelatedItemEditor::VALUE_DIVIDER . ucfirst($object->__toString()));
	}

	/**
	 * Adds objects as options in a dropdown list
	 *
	 * @param XhtmlSelect $select
	 * @param object[] $a_objects_to_add
	 * @param int[] $a_objects_to_disable
	 * @return XhtmlSelect
	 */
	protected function AddOptions(XhtmlSelect $select, $a_objects_to_add, $a_objects_to_disable)
	{
		foreach ($a_objects_to_add as $i_id => $obj)
		{
			$opt = $this->CreateOption($i_id, $obj);
			if (in_array($i_id, $a_objects_to_disable, true)) $this->DisableOption($opt);
			$select->AddControl($opt);
		}
		return $select;

	}

	/**
	 * Alters an option so that it looks disabled
	 *
	 * @param XhtmlOption $opt
	 * @return XhtmlOption
	 */
	protected function DisableOption(XhtmlOption $opt)
	{
		$opt->AddAttribute('value', '');
		$s_style = trim($opt->GetAttribute('style'));
		$i_style_len = strlen($s_style);
		if ($i_style_len > 0 and strrpos($s_style, ';') != $i_style_len) $s_style .= ';';
		$s_style .= 'color: #aaa;';
		$opt->AddAttribute('style', $s_style);
		return $opt;
	}

	/**
	 * Create validators to check a single value
	 *
	 * @param string $s_item_suffix
	 */
	protected function CreateValidatorsForItem($s_item_suffix='')
	{
		if ($this->AddClicked())
		{
			require_once('data/validation/required-field-validator.class.php');
			$this->AddValidator(new RequiredFieldValidator(array($this->GetNamingPrefix() . $this->s_data_object_class), 'Please select an item before clicking \'Add\''));
		}
	}
}
?>
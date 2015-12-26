<?php
class Collection implements IteratorAggregate
{
	var $a_items = array();
	var $s_item_class;
	private $i_position_counter = -1;

	/**
	 * @return Collection
	 * @param array $a_items
	 * @param string $item_class
	 * @desc Create a collection and instantiate the internal array
	 */
	function Collection($a_items=null, $item_class=null)
	{
		# optionally instantiate with array
		$this->SetItems($a_items);
		if (!is_null($item_class)) $this->SetItemClass($item_class);
	}

	/**
	 * @return void
	 * @param array $a_input
	 * @desc Use the supplied array of items as the basis of the collection
	 */
	function SetItems($a_input)
	{
		if (is_array($a_input)) $this->a_items = $a_input; else $this->a_items = array();
	}

	/**
	 * @return array
	 * @desc Get an array containing the items in the collection
	 */
	function GetItems()
	{
		return $this->a_items;
	}

	/**
	 * @return void
	 * @desc Clears all existing data out of the array and resets the counter
	 */
	function Clear()
	{
		$this->a_items = array();
		$this->ResetCounter();
	}

	/**
	 * @return void
	 * @param mixed $o_input
	 * @desc Add an item to the end of the collection
	 */
	function Add($o_input)
	{
		if (!$this->s_item_class or $o_input instanceof $this->s_item_class)
		{
			return array_push($this->a_items, $o_input)-1;
		}
		else if ($o_input) throw new Exception('Item is wrong type for collection');
	}

	function Insert($o_input)
	{
		if (!$this->s_item_class or $o_input instanceof $this->s_item_class) array_unshift($this->a_items, $o_input);
	}

	/**
	 * @return object
	 * @desc Get the first item from the collection
	 */
	function GetFirst()
	{
		if (is_array($this->a_items) and count($this->a_items) > 0) return $this->a_items[min(array_keys($this->a_items))]; else return null;
	}

	/**
	 * @return object
	 * @desc Gets the final item in the collection
	 */
	function GetFinal()
	{
		if (is_array($this->a_items) and count($this->a_items) > 0) return $this->a_items[max(array_keys($this->a_items))]; else return null;
	}

	/**
	 * @return object
	 * @param int $i_index
	 * @desc Get an item from the collection corresponding to the provided zero-based index
	 */
	public function GetByIndex($i_index)
	{
		if (is_numeric($i_index) and is_array($this->a_items) and isset($this->a_items[(int)$i_index])) return $this->a_items[(int)$i_index]; else return null;
	}

	/**
	 * Searchs the array for the given value and returns the first matching index, or false if not found
	 *
	 * @param mixed $m_value
	 * @return mixed
	 */
	public function IndexOf($m_value)
	{
		return array_search($m_value, $this->a_items);
	}

	/**
	 * Gets whether the specified value is in the collection
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function Contains($value)
	{
		return in_array($value, $this->a_items, true);
	}

	/**
	 * @return int
	 * @desc Get the number of items in the Collection
	 */
	function GetCount()
	{
		return count($this->a_items);
	}

	/**
	 * @return void
	 * @desc Resets the iteration counter back to the start of the collection
	 */
	function ResetCounter()
	{
		$this->i_position_counter = -1;
	}

	/**
	 * @return bool
	 * @desc When iterating through the collection, move to the next item
	 */
	function MoveNext()
	{
		if (($this->i_position_counter +1) >= $this->GetCount()) return false;
		else
		{
			$this->i_position_counter++;
			return true;
		}
	}

	/**
	 * @return mixed
	 * @desc Get a single item from the collection at the current position
	 */
	function GetItem()
	{
		if (is_array($this->a_items) and $this->i_position_counter > -1) return $this->a_items[$this->i_position_counter]; else return null;
	}

	/**
	 * @return void
	 * @param string $s_input
	 * @desc Sets the base class of objects in the collection
	 */
	protected function SetItemClass($s_input)
	{
		$this->s_item_class = (string)$s_input;
	}

	/**
	 * @return string
	 * @desc Gets the base class of objects in the collection
	 */
	protected function GetItemClass()
	{
		return $this->s_item_class;
	}

	/**
	 * Gets an iterator for the collection (implements IteratorAggregate)
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayObject($this->a_items);
	}

	/**
	 * Sorts the objects in the collection by the given property
	 *
	 * @param string $s_property
	 */
	public function SortByProperty($s_property)
	{
		$a_sort_controller = array();
		foreach($this->a_items as $i_key => $o_obj)
		{
			$a_sort_controller[$i_key] = (is_object($o_obj) and method_exists($o_obj, $s_property)) ? $o_obj->$s_property() : null;
		}

		array_multisort($a_sort_controller, $this->a_items);
	}

	/**
	 * Gets the first item in the collection with the given value for the given property
	 *
	 * @param string $property
	 * @param string $value
	 */
	public function GetItemByProperty($property, $value)
	{
		foreach($this->a_items as $i_key => $o_obj)
		{
			if (is_object($o_obj) and method_exists($o_obj, $property) and $o_obj->$property() == $value) return $o_obj;
		}

		return null;
	}

	/**
	 * Gets the index of the first item in the collection with the given value for the given property
	 *
	 * @param string $property
	 * @param string $value
	 * @return int
	 */
	public function GetItemIndexByProperty($property, $value)
	{
		foreach($this->a_items as $i_key => $o_obj)
		{
			if (is_object($o_obj) and method_exists($o_obj, $property) and $o_obj->$property() == $value) return $i_key;
		}

		return null;
	}


	/**
	 * Deletes items in the collection with the given value for the given property
	 *
	 * @param string $property
	 * @param string $value
	 * @param Maximum number of matching items to delete $i_max_items
	 * @return int Number of deleted items
	 */
	public function DeleteItemByProperty($property, $value, $i_max_items=null)
	{
		$i_deleted = 0;
		$a_delete_keys = array();
		foreach($this->a_items as $i_key => $o_obj)
		{
			if (is_object($o_obj) and method_exists($o_obj, $property) and $o_obj->$property() == $value)
			{
				$a_delete_keys[] = $i_key; # don't delete yet, might complicate foreach
				$i_deleted++;
				if (!is_null($i_max_items) and $i_deleted >= $i_max_items) break; # Option to drop out of loop early if we want to
			}
		}

		foreach ($a_delete_keys as $i_key) unset($this->a_items[$i_key]);

		return $i_deleted;
	}
}
?>
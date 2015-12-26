<?php
/**
 * An item in a media gallery
 *
 */
class GalleryItem
{
	private $item_in_gallery;
	private $item_url_in_gallery;
	private $b_for_deletion = false;

	/**
	 * Creates a GalleryItem
	 * @param object $item
	 * @param string $url
	 * @return void
	 */
	public function __construct($item, $url)
	{
		$this->SetItem($item);
		$this->SetUrl($url);
	}

	/**
	 * Sets the item which appears in the gallery
	 * @param object $item
	 * @return void
	 */
	public function SetItem($item) { $this->item_in_gallery = $item; }

	/**
	 * Gets the item which appears in the gallery
	 * @return object
	 */
	public function GetItem() { return $this->item_in_gallery; }

	/**
	 * Gets the id of the item which appears in the gallery
	 * @return int
	 */
	public function GetItemId()
	{
		if (!is_object($this->item_in_gallery)) return null;
		if (!method_exists($this->item_in_gallery, 'GetId')) return null;
		return $this->item_in_gallery->GetId();
	}

	/**
	 * Sets the URL of the item as it appears in the containing gallery
	 * @param string $url
	 * @return void
	 */
	public function SetUrl($url) { $this->item_url_in_gallery = $url; }

	/**
	 * Gets the URL of the item as it appears in the containing gallery
	 * @return string
	 */
	public function GetUrl() { return $this->item_url_in_gallery; }



	/**
	 * Sets whether the item is to be deleted from the gallery
	 *
	 * @param bool $b_to_delete
	 */
	public function SetIsForDeletion($b_to_delete)
	{
		$this->b_for_deletion = (bool)$b_to_delete;
	}

	/**
	 * Gets whether the item is to be deleted from the gallery
	 *
	 * @return bool
	 */
	public function GetIsForDeletion()
	{
		return $this->b_for_deletion;
	}
}
?>
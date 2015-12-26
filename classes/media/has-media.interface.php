<?php
interface IHasMedia
{
	/**
	 * Gets a content type identifier for a type of content which can have related media items
	 *
	 */
	public function GetContentType();

	/**
	 * Gets the unique identifier for the instance of the content type with related media items
	 *
	 */
	public function SetId($id);

	/**
	 * Sets the unique identifier for the instance of the content type with related media items
	 *
	 */
	public function GetId();
}
?>
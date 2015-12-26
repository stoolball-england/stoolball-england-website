<?php
class QueueAction
{
	/**
	 * An image is queued for later deletion
	 *
	 * @return int
	 */
	const IMAGE_DELETE = 1;

	/**
	 * If this action is present, an email has already been sent about checking uploaded images
	 *
	 * @return int
	 */
	const IMAGE_NO_CHECK_EMAIL = 2;

	/**
	 * The specified match was just added but the moderator has not been notified
	 * @var int
	 */
	const MATCH_ADDED = 3;

	/**
	 * The specified match was just updated but the moderator has not been notified
	 * @var int
	 */
	const MATCH_UPDATED = 4;
}
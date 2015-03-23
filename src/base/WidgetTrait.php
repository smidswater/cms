<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\elements\User;

/**
 * WidgetTrait implements the common methods and properties for dashboard widget classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait WidgetTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var integer The ID of the user that owns the widget
	 */
	public $userId;

	/**
	 * @var integer The widget’s sort order
	 */
	public $sortOrder;

	// Public Methods
	// =========================================================================

	/**
	 * Returns user that owns the widget
	 *
	 * @return User the user that owns the widget
	 */
	public function getUser()
	{
		return Craft::$app->users->getUserById($this->userId);
	}
}

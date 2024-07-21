<?php
/**
 * @author    : Jakiboy
 * @package   : VanillePlugin
 * @version   : 1.0.x
 * @copyright : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link      : https://jakiboy.github.io/VanillePlugin/
 * @license   : MIT
 *
 * This file if a part of VanillePlugin Framework.
 */

declare(strict_types=1);

namespace VanilleLicense\tr;

use VanilleLicense\License;

/**
 * Define licensing functions.
 */
trait TraitLicensing
{
	/**
	 * Activate license.
	 *
	 * @access public
	 * @inheritdoc
	 */
	public function activateLicense(?bool &$status = null) : array
	{
		return (new License())->activate($status);
	}

	/**
	 * Validate license.
	 *
	 * @access public
	 * @inheritdoc
	 */
	public function validateLicense() : bool
	{
		return (new License())->validate();
	}

	/**
	 * Check whether plugin is licensed.
	 *
	 * @access public
	 * @inheritdoc
	 */
	public function isLicensed() : bool
	{
		return (new License())->isLicensed();
	}

	/**
	 * Get license data.
	 *
	 * @access public
	 * @inheritdoc
	 */
	public function getLicense() : array
	{
		return (new License())->getData();
	}

	/**
	 * Load license file.
	 *
	 * @access protected
	 * @inheritdoc
	 */
	protected function loadLicense(bool $force = false, ?string $file = null) : bool
	{
		return (new License())->load($force, $file);
	}

	/**
	 * Reset license data.
	 *
	 * @access protected
	 * @inheritdoc
	 */
	protected function resetLicense() : bool
	{
		return (new License())->reset();
	}
}

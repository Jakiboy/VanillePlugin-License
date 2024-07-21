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

namespace VanilleLicense;

use VanilleLicense\inc\Webservice;

class License extends Webservice
{
	use \VanillePlugin\tr\TraitDatable;

	/**
	 * @access protected
	 */
	protected const OPTION  = 'activation';
	protected const DATA    = 'license';
	protected const VERSION = 'lite';
	protected const PACK    = '1s';
	protected const DATE    = 'd/m/Y H:i';
	protected const EXPIRE  = 0;
	protected const MULTI   = false;
	protected const CONTENT = [
		'version' => null,
		'pack'    => null,
		'date'    => null,
		'expire'  => null,
		'quota'   => []
	];
	protected const CREDENTIALS = [
		'token' => null,
		'user'  => null,
		'pswd'  => null,
		'key'   => null
	];

	/**
	 * @access protected
	 */
	protected const MESSAGE = [
		'unknown-error'    => 'Unknown error, please contact support',
		'server-down'      => 'Unable to connect to server, please try again',
		'empty-response'   => 'No response, please try again',
		'expired-license'  => 'Your license is expired, please renew it',
		'invalid-response' => 'Invalid response, please try again',
		'invalid-version'  => 'Invalid license version, please upgrade your license',
		'invalid-key'      => 'Your license key is invalid, please buy new license',
		'invalid-format'   => 'Your license key is invalid, please check your entry',
		'invalid-auth'     => 'Please verify your email and password',
		'invalid-quota'    => 'Your quota domain limit reached! please upgrader your license',
		'missing-domain'   => 'Your website is not authorized, please contact support',
		'missing-key'      => 'Your license key is invalid, please contact support',
	];

	/**
	 * @access protected
	 */
	protected const ERROR = [
		'invalid-auth'     => 'Unauthorized',
		'invalid-key'      => 'Invalid license key',
		'invalid-format'   => 'Invalid license key format',
		'invalid-version'  => 'Invalid license version',
		'invalid-quota'    => 'Quota domain limit reached',
		'expired-license'  => 'Expired license',
		'missing-domain'   => 'Domain not provided',
		'missing-key'      => 'License key not provided',
	];

	/**
	 * @inheritdoc
	 */
	public function __construct(array $args = [])
	{
		parent::__construct($this->getCredentials(), $args);
	}

	/**
	 * Load license file.
	 *
	 * @access public
	 * @param bool $force
	 * @param string $file
	 * @return bool
	 */
	public function load(bool $force = false, ?string $file = null) : bool
	{
		if ( !$force && !$this->isNew() ) {
			return false;
		}

		if ( !$file ) {
			$file = $this->getRoot('.license');
		}

		if ( $this->isReadable($file, true) ) {

			$token = $this->readFile($file);
			$data  = $this->getCredentials();
			$data['token'] = (string)$token;

			if ( $this->setCredentials($data) ) {
				$this->removeFile($file, $this->getRoot());
				$this->auth = $data;
				return $this->isValid();
			}

		}

		return false;
	}

	/**
	 * Activate license.
	 *
	 * @access public
	 * @param bool $status
	 * @return array
	 */
	public function activate(?bool &$status = null) : array
	{
		$this->auth = $this->parseCredentials();
		$status = $this->isValid($data);
		return $data;
	}

	/**
	 * Validate license.
	 *
	 * @access public
	 * @return bool
	 */
	public function validate() : bool
	{
		return $this->isValid();
	}

	/**
	 * Check whether license is valid.
	 *
	 * @access public
	 * @return bool
	 */
	public function isValid(?array &$data = []) : bool
	{
		// Server down
		if ( $this->check()->isDown() ) {
			$this->setBaseUrl((string)static::BACKUP);
		}

		// Backup server down
		if ( $this->check()->isDown() ) {
			return true;
		}
		
		// Get response
		$data = $this->decodeJson($this->body(), true);

		// No response
		if ( !$data ) {
			$this->disable(static::MESSAGE['empty-response']);
			return false;
		}

		// Response error
		if ( $this->hasError() ) {
			$this->disable($this->parseError($data));
			return false;
		}

		$code   = $data['code'] ?? false;
		$status = $data['status'] ?? false;

		if ( $code == self::UP && $status == 'success' ) {

			// Version error
			$content = $data['content'] ?? [];
			$content = $this->mergeArray(static::CONTENT, $content);

			if ( $content['version'] == 'api' ) {
				$this->disable(static::MESSAGE['invalid-version']);
				return false;
			}

			$this->enable($content);
			return true;

		}

		return false;
	}

	/**
	 * Get license data.
	 *
	 * @access public
	 * @return array
	 */
	public function getData() : array
	{
		return (array)$this->getPluginOption(
			static::DATA,
			static::CONTENT,
			static::MULTI
		);
	}

	/**
	 * Set license data.
	 *
	 * @access public
	 * @param array $data
	 * @return bool
	 */
	public function setData(array $data) : bool
	{
		$data = $this->mergeArray($this->getData(), $data);
		return $this->updatePluginOption(static::DATA, $data, static::MULTI);
	}

	/**
	 * Get license version.
	 *
	 * @access public
	 * @return string
	 */
	public function getVersion() : string
	{
		$data = $this->getData();
		$version = $data['version'] ?? static::VERSION;
		return (string)$version;
	}

	/**
	 * Get license expiration.
	 *
	 * @access public
	 * @return int
	 */
	public function getExpire() : int
	{
		$data = $this->getData();
		$expire = $data['expire'] ?? static::EXPIRE;
		return (int)$expire;
	}

	/**
	 * Check license expiration.
	 *
	 * @access public
	 * @return bool
	 */
	public function isExpired() : bool
	{
		return ($this->getExpire() === static::EXPIRE);
	}

	/**
	 * Check new install.
	 *
	 * @access public
	 * @return bool
	 */
	public function isNew() : bool
	{
		$data = $this->getData();
		$expire = $data['expire'] ?? null;
		return ($expire === null);
	}

	/**
	 * Check whether license is activated (Alias).
	 *
	 * @access public
	 * @return bool
	 */
	public function isActivated() : bool
	{
		return !$this->isExpired();
	}

	/**
	 * Check whether license is disabled (Alias).
	 *
	 * @access public
	 * @return bool
	 */
	public function isDisabled() : bool
	{
		return $this->isExpired();
	}

	/**
	 * Check whether plugin is licensed (Alias).
	 *
	 * @access public
	 * @return bool
	 */
	public function isLicensed() : bool
	{
		return (!$this->isExpired() && !$this->isNew());
	}

	/**
	 * Reset license data.
	 *
	 * @access public
	 * @return bool
	 */
	public function reset() : bool
	{
		$this->deletePluginTransient('license-error');
		$this->resetPluginOption('activation');
		return $this->setData(static::CONTENT);
	}
	
	/**
	 * Index endpoint.
     * [GET: /].
	 *
	 * @access protected
	 * @return object
	 */
	protected function index() : self
	{
		return $this->send('/');
	}

	/**
	 * Check license endpoint.
     * [GET: /license/check/].
	 *
	 * @access protected
	 * @return object
	 */
	protected function check() : self
	{
		$this->setBody([
			'domain' => $this->geSiteDomain(),
			'key'    => $this->auth['key'] ?? false
		]);
		return $this->send('/license/check/');
	}

	/**
	 * Get license credentials from option.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getCredentials() : array
	{
		$data = $this->getPluginOption(static::OPTION, [], static::MULTI);
		return $data ?: [];
	}

	/**
	 * Parse license credentials from request.
	 *
	 * @access protected
	 * @return array
	 */
	protected function parseCredentials() : array
	{
		$data = $this->getHttpPost(static::OPTION) ?: [];
		$data = $this->applyPluginFilter('credentials', $data);
		$this->updatePluginOption(static::OPTION, $data, static::MULTI);
		return (array)$data;
	}

	/**
	 * Set license credentials.
	 *
	 * @access protected
	 * @param array $data
	 * @return bool
	 */
	protected function setCredentials(array $data) : bool
	{
		$data = $this->applyPluginFilter('credentials', $data);
		return $this->updatePluginOption(static::OPTION, $data, static::MULTI);
	}

	/**
	 * Disable license.
	 *
	 * @access protected
	 * @param string $error
	 * @return bool
	 */
	protected function disable(?string $error = null) : bool
	{
		if ( $error ) {
			$this->setPluginTransient('license-error', $error);
		}
		return $this->setData(['expire' => static::EXPIRE]);
	}

	/**
	 * Enable license.
	 *
	 * @access protected
	 * @param array $content
	 * @return bool
	 */
	protected function enable(array $content) : bool
	{
		$current = $this->getDate('now', 'Y-m-d H:i:s', true);
		$expire  = $content['expire'] ?? $current;
		$date    = $this->createDate($expire, static::DATE);
		$expire  = $this->getDateDiff($current, $date, '%R%a') + 1;
		$date    = $this->dateToString($date, static::DATE);

		$this->deletePluginTransient('license-error');
		return $this->setData([
			'expire'  => $expire,
			'date'    => $date,
			'version' => $content['version'] ?? static::VERSION,
			'pack'    => $content['pack'] ?? static::PACK
		]);
	}
}

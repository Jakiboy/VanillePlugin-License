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

use VanillePlugin\lib\API;

class Webservice extends API
{
	/**
	 * @access public
	 * @var int DOWN, Down status code
	 * @var string HOST, Server url
	 * @var string BACKUP, Backup server url
	 */
	public const DOWN   = 429;
	public const HOST   = null;
	public const BACKUP = null;

	/**
	 * @access protected
	 * @var array MESSAGE, Server message
	 * @var array ERROR, Server error
	 * @var array CONTENT, Server default content
	 */
	protected const MESSAGE = [
		'unknown-error' => 'Unknown error, please contact support'
	];
	protected const ERROR   = [];
	protected const CONTENT = [];
	
	/**
	 * Init Webservice request.
	 * [Filter: {plugin}-webservice-timeout].
	 *
	 * @access protected
	 * @param array $auth
	 * @param array $args
	 */
	protected function __construct(array $auth = [], array $args = [])
	{
		// Set auth
		$this->auth = $this->mergeArray($this->getRemoteServer(), $auth);

		// Set host
		$url = static::HOST ?: $this->auth['host'];

		$timeout = $this->applyPluginFilter('webservice-timeout', 30);
		$args = $this->mergeArray([
			'timeout' => $timeout
		], $args);

		parent::__construct(self::GET, $args, $url);
	}

	/**
	 * Index.
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
	 * Parse response error.
	 *
	 * @access protected
	 * @param array $response
	 * @return string
	 */
	protected function parseError(array $response) : string
	{
		$message = $response['message'] ?? false;
		$error = $this->arrayKeys(static::ERROR, $message, true);
		$error = $error[0] ?? 'unknown-error';
		return static::MESSAGE[$error];
	}
}

<?php

namespace App\controllers;

use Exception;
use OObject;

class cTestController extends OObject
{
	public function test()
	{
		$this->data = [
			'Success message',
		];
	}

	public function withQuery($params = [])
	{
		$this->data = [
			'params' => $params,
		];
	}

	/**
	 * @throws \Exception
	 */
	public function throwException()
	{
		throw new Exception('Expected exception thrown in ' . __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function index()
	{
		throw new Exception('Expected exception thrown in ' . __FUNCTION__);
	}

	public function getPermissions()
	{
		return [
			'object' => 'any',
			'test' => 'any',
			'throwException' => 1,
			'index' => 1,
		];
	}
}

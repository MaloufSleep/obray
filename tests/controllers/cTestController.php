<?php

namespace tests\controllers;

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
}

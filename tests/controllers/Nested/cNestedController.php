<?php

namespace tests\controllers\Nested;

use OObject;

class cNestedController extends OObject
{
	public function index()
	{
		$this->data = __FUNCTION__;
	}
}

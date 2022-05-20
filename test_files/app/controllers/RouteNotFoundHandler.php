<?php

namespace App\controllers;

use OObject;

class RouteNotFoundHandler extends OObject
{
	public array $permissions = [
		'object' => 'any',
	];

	public function missing()
	{
		$this->data = 'Success';
	}
}

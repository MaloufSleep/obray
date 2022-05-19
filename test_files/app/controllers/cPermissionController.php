<?php

namespace App\controllers;

use OObject;

class cPermissionController extends OObject
{
	public array $permissions = [
		'object' => 'any',
		'public' => 'any',
		'user' => 'user',
		'nonGraduated' => 1,
		'graduated' => 1,
		'permissionsAndRoles' => [
			'permissions' => ['permission_access'],
			'roles' => ['role_access'],
		],
	];

	public function public()
	{
	}

	public function noPermissionsListed()
	{
	}

	public function user()
	{
	}

	public function nonGraduated()
	{
	}

	public function graduated()
	{
	}

	public function permissionsAndRoles()
	{
	}
}

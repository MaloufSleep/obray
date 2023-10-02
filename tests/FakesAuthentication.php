<?php

namespace tests;

trait FakesAuthentication
{
	protected function authenticate()
	{
		global $_SESSION;
		$_SESSION['ouser'] = new class {
			public $ouser_id = 1;
			public $ouser_permission_level = 1;
		};
	}
	protected function unauthenticate()
	{
		global $_SESSION;
		unset($_SESSION['ouser']);
	}
}

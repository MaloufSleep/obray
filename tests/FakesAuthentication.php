<?php

namespace tests;

use stdClass;

trait FakesAuthentication
{
	protected function authenticate()
	{
		global $_SESSION;
		$_SESSION['ouser'] = new stdClass();
        $_SESSION['ouser']->ouser_id = 1;
        $_SESSION['ouser']->ouser_permission_level = 1;
	}

	protected function unauthenticate()
	{
		global $_SESSION;
		unset($_SESSION['ouser']);
	}
}

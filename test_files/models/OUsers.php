<?php

use tests\FakesAuthentication;

class OUsers extends OObject
{
	use FakesAuthentication;

	public static $shouldAuthenticate = true;

	public function login()
	{
		if (static::$shouldAuthenticate) {
			$this->authenticate();
		}
	}
}

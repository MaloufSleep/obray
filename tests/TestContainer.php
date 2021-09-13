<?php

namespace tests;

use Psr\Container\ContainerInterface;

class TestContainer implements ContainerInterface
{
	protected $resolved = [];

	protected $bindings = [];

	public function instance(string $id, $instance)
	{
		$this->resolved[$id] = $instance;
	}

	public function bind(string $id, $callback)
	{
		$this->bindings[$id] = $callback;
	}

	public function get(string $id)
	{
		if ($id[0] === '\\') {
			$id = substr($id, 1);
		}

		if (array_key_exists($id, $this->resolved)) {
			return $this->resolved[$id];
		} elseif (array_key_exists($id, $this->bindings)) {
			return $this->bindings[$id]();
		} else {
			return new $id;
		}
	}

	public function has(string $id): bool
	{
		return true;
	}
}

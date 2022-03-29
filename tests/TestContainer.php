<?php

namespace tests;

use Closure;
use Illuminate\Contracts\Container\Container;

class TestContainer implements Container
{
	protected $resolved = [];

	protected $bindings = [];

	public function instance($id, $instance)
	{
		$this->resolved[$id] = $instance;
	}

	public function bind($id, $callback = null, $shared = false)
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

	public function bound($abstract)
	{
		// TODO: Implement bound() method.
	}

	public function alias($abstract, $alias)
	{
		// TODO: Implement alias() method.
	}

	public function tag($abstracts, $tags)
	{
		// TODO: Implement tag() method.
	}

	public function tagged($tag)
	{
		// TODO: Implement tagged() method.
	}

	public function bindIf($abstract, $concrete = null, $shared = false)
	{
		// TODO: Implement bindIf() method.
	}

	public function singleton($abstract, $concrete = null)
	{
		// TODO: Implement singleton() method.
	}

	public function singletonIf($abstract, $concrete = null)
	{
		// TODO: Implement singletonIf() method.
	}

	public function extend($abstract, Closure $closure)
	{
		// TODO: Implement extend() method.
	}

	public function addContextualBinding($concrete, $abstract, $implementation)
	{
		// TODO: Implement addContextualBinding() method.
	}

	public function when($concrete)
	{
		// TODO: Implement when() method.
	}

	public function factory($abstract)
	{
		// TODO: Implement factory() method.
	}

	public function flush()
	{
		// TODO: Implement flush() method.
	}

	public function make($abstract, array $parameters = [])
	{
		return $this->get($abstract);
	}

	public function call($callback, array $parameters = [], $defaultMethod = null)
	{
		// TODO: Implement call() method.
	}

	public function resolved($abstract)
	{
		// TODO: Implement resolved() method.
	}

	public function resolving($abstract, Closure $callback = null)
	{
		// TODO: Implement resolving() method.
	}

	public function afterResolving($abstract, Closure $callback = null)
	{
		// TODO: Implement afterResolving() method.
	}
}

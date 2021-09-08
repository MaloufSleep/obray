<?php

namespace tests\Object;

use Symfony\Component\Console\Output\TrimmedBufferOutput;
use tests\TestCase;

/**
 * @covers OObject
 */
class ConsoleTest extends TestCase
{
	/**
	 * @var \Symfony\Component\Console\Output\TrimmedBufferOutput
	 */
	protected $output;

	protected function setUp(): void
	{
		parent::setUp();

		$this->router->setOutput($this->output = new TrimmedBufferOutput(PHP_INT_MAX));
	}

	public function testSprintf()
	{
		$this->router->console('%s', 'Hello!');

		$this->assertSame('Hello!', $this->output->fetch());
	}

	public function testColoredOutput()
	{
		$this->router->console('%s', 'Hello!', 'Red');

		$this->assertSame("\033[31mHello!\033[0m", $this->output->fetch());
	}

	public function testArray()
	{
		$this->router->console(['Hello!']);
		$this->assertSame(print_r(['Hello!'], true), $this->output->fetch());
	}
}

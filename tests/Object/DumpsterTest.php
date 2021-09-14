<?php

namespace tests\Object;

use tests\TestCase;

/**
 * @covers \OObject::dumpster
 */
class DumpsterTest extends TestCase
{
	public function testDumpster()
	{
		ob_start();

		$this->router->dumpster('hello', true);

		$output = ob_get_clean();

		$this->assertSame('<pre>hello</pre>', $output);
	}
}

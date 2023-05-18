<?php

namespace tests\Object;

use Exception;
use tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    /**
     * @covers OObject
     */
    public function testExceptionCanBeThrown()
    {
        \OObject::$handleExceptions = false;
        $this->expectException(Exception::class);
        $response = $this->route('LegacyExceptionThrower/throw');
    }
}

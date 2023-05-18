<?php

namespace tests\Object;

use Exception;
use OObject;
use tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OObject::$handleExceptions = false;
    }

    /**
     * @covers OObject
     */
    public function testExceptionCanBeThrown()
    {
        $this->expectException(Exception::class);
        $this->route('LegacyExceptionThrower/throw');
    }

    /**
     * @covers OObject
     */
    public function testNestedExceptionCanBeThrown()
    {
        $this->expectException(Exception::class);
        $this->route('LegacyExceptionThrower/nestToThrow');
    }
}

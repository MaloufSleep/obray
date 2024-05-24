<?php

namespace tests\Odbo;

use ODBO;
use PDO;
use tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @covers \buildDefaultPdoObject
 * @covers \getDatabaseConnection
 * @covers \getReaderDatabaseConnection
 * @covers \ODBO::setPdoResolver
 * @covers \ODBO::setReadPdoResolver
 * @covers \ODBO::getPdoResolver
 * @covers \ODBO::getReadPdoResolver
 */
class ResolversTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $conn, $readConn;

        unset($conn);
        unset($readConn);
    }

    public function testDefaultPdoObjectIsBuilt(): void
    {
        $conn = getDatabaseConnection();
        $this->assertInstanceOf(PDO::class, $conn);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $conn->getAttribute(PDO::ATTR_ERRMODE));

        // Reader not defined, should get reader back
        $reader = getReaderDatabaseConnection();
        $this->assertSame($conn, $reader);

        global $readConn;
        unset($readConn);

        define('__OBRAY_DATABASE_HOST_READER__', __OBRAY_DATABASE_HOST__);
        $conn = getReaderDatabaseConnection();
        $this->assertInstanceOf(PDO::class, $conn);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $conn->getAttribute(PDO::ATTR_ERRMODE));

        // Ensure it works again, returning same object
        $new = getReaderDatabaseConnection();
        $this->assertSame($conn, $new);
    }

    public function testResolversAreCalled(): void
    {
        $f = function () {
            $conn = buildDefaultPdoObject(
                __OBRAY_DATABASE_HOST__,
                __OBRAY_DATABASE_NAME__,
                __OBRAY_DATABASE_USERNAME__,
                __OBRAY_DATABASE_PASSWORD__
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            return $conn;
        };
        ODBO::setPdoResolver($f);
        ODBO::setReadPdoResolver($f);

        $conn = getDatabaseConnection();
        $this->assertInstanceOf(PDO::class, $conn);
        $this->assertSame(PDO::ERRMODE_SILENT, $conn->getAttribute(PDO::ATTR_ERRMODE), 'Error mode is not set to silent.');

        $conn = getReaderDatabaseConnection();
        $this->assertInstanceOf(PDO::class, $conn);
        $this->assertSame(PDO::ERRMODE_SILENT, $conn->getAttribute(PDO::ATTR_ERRMODE), 'Error mode is not set to silent.');
    }
}

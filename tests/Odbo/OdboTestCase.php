<?php

namespace tests\Odbo;

use DateTime;
use ODBO;
use PDO;
use tests\Models\TestModel;
use tests\ResetsDatabase;
use tests\TestCase;

class OdboTestCase extends TestCase
{
    use ResetsDatabase;

    protected ODBO $testModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeResetsDatabase();

        $this->testModel = new TestModel();
        $this->testModel->dbh = getDatabaseConnection(true);
    }

    protected function assertTimestampsEqualsWithDelta(DateTime|string $expected, DateTime|string $actual, $delta = 1)
    {
        if (is_string($expected)) {
            $expected = new DateTime($expected);
        }
        if (is_string($actual)) {
            $actual = new DateTime($actual);
        }

        $this->assertEqualsWithDelta($expected->getTimestamp(), $actual->getTimestamp(), $delta);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @covers \buildDefaultPdoObject
     */
    public function testPdoAttributesArray(): void
    {
        define('__OBRAY_DATABASE_ATTRIBUTES__', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        ]);
        $conn = getDatabaseConnection(true);
        $this->assertInstanceOf(PDO::class, $conn);
        $this->assertSame(PDO::ERRMODE_SILENT, $conn->getAttribute(PDO::ATTR_ERRMODE));
    }
}

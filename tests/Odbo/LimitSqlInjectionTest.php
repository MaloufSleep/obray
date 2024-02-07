<?php

namespace tests\Odbo;

use stdClass;

/**
 * @covers \ODBO::get
 */
class LimitSqlInjectionTest extends OdboTestCase
{
    public function testLimitIsRejectedWhenNotNumeric()
    {
        $this->testModel->get([
            'start' => 0,
            'rows' => 'a',
        ]);

        $this->assertNotError($this->testModel);
    }

    public function testLimitStillWorks()
    {
        $this->pdo->query('INSERT INTO ' . $this->testModel->table . ' (column_int, column_string) VALUES (1, "one")');
        $firstId = $this->pdo->lastInsertId();
        $this->pdo->query('INSERT INTO ' . $this->testModel->table . ' (column_int, column_string) VALUES (2, "two")');
        $secondId = $this->pdo->lastInsertId();

        $this->testModel->get([
            'start' => 0,
            'rows' => 1,
        ]);

        $data = $this->assertSuccessfulReponse();
        $this->assertFirstId($data, $firstId);

        $this->testModel->get([
            'start' => 1,
            'rows' => 1,
        ]);

        $data = $this->assertSuccessfulReponse();
        $this->assertSecondId($data, $secondId);

        $this->testModel->get([
            'start' => 0,
            'rows' => 2,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($this->testModel->data);
        $this->assertCount(2, $this->testModel->data);
        $this->assertArrayHasKey(0, $this->testModel->data);
        $this->assertInstanceOf(stdClass::class, $first = $this->testModel->data[0]);
        $this->assertArrayHasKey(1, $this->testModel->data);
        $this->assertInstanceOf(stdClass::class, $second = $this->testModel->data[1]);

        $this->assertFirstId($first, $firstId);
        $this->assertSecondId($second, $secondId);
    }

    /**
     * @param $data
     * @param $firstId
     * @return void
     */
    protected function assertFirstId($data, $firstId): void
    {
        $this->assertObjectHasProperty('id', $data);
        $this->assertSame($firstId, $data->id);
        $this->assertObjectHasProperty('column_int', $data);
        $this->assertSame('1', $data->column_int);
        $this->assertObjectHasProperty('column_string', $data);
        $this->assertSame('one', $data->column_string);
    }

    /**
     * @param $data
     * @param $secondId
     * @return void
     */
    protected function assertSecondId($data, $secondId): void
    {
        $this->assertObjectHasProperty('id', $data);
        $this->assertSame($secondId, $data->id);
        $this->assertObjectHasProperty('column_int', $data);
        $this->assertSame('2', $data->column_int);
        $this->assertObjectHasProperty('column_string', $data);
        $this->assertSame('two', $data->column_string);
    }

    /**
     * @return mixed
     */
    protected function assertSuccessfulReponse()
    {
        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($this->testModel->data);
        $this->assertCount(1, $this->testModel->data);
        $this->assertArrayHasKey(0, $this->testModel->data);
        $this->assertInstanceOf(stdClass::class, $data = $this->testModel->data[0]);
        return $data;
    }
}

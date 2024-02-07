<?php

namespace tests\Odbo;

use stdClass;

/**
 * @covers \ODBO::update
 */
class UpdateTest extends OdboTestCase
{
    protected string $modelId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo->query("INSERT INTO {$this->testModel->table} (column_int, column_string) VALUES (1, 'blah')");
        $this->modelId = $this->pdo->lastInsertId();

        $this->testModel->dbh = $this->pdo;
    }

    public function testUpdate()
    {
        $this->testModel->update([
            'id' => $this->modelId,
            'column_int' => 100,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($data = $this->testModel->data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertInstanceOf(stdClass::class, $model = $data[0]);

        $this->assertObjectHasProperty('id', $model);
        $this->assertSame($this->modelId, $model->id);
        $this->assertObjectHasProperty('column_int', $model);
        $this->assertSame('100', $model->column_int);
        $this->assertObjectHasProperty('column_string', $model);
        $this->assertSame('blah', $model->column_string);
        $this->assertObjectHasProperty('OCDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
        $this->assertObjectHasProperty('OMDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
        $this->assertObjectHasProperty('OCU', $model);
        $this->assertNull($model->OCU);
        $this->assertObjectHasProperty('OMU', $model);
        $this->assertNull($model->OMU);
    }

    public function testUpdateNullAttribute()
    {
        $this->testModel->update([
            'id' => $this->modelId,
            'column_int' => 1,
            'column_string' => null,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($data = $this->testModel->data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertInstanceOf(stdClass::class, $model = $data[0]);

        $this->assertObjectHasProperty('id', $model);
        $this->assertSame('1', $model->id);
        $this->assertObjectHasProperty('column_int', $model);
        $this->assertSame('1', $model->column_int);
        $this->assertObjectHasProperty('column_string', $model);
        $this->assertSame(null, $model->column_string);
        $this->assertObjectHasProperty('OCDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
        $this->assertObjectHasProperty('OMDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
        $this->assertObjectHasProperty('OCU', $model);
        $this->assertNull($model->OCU);
        $this->assertObjectHasProperty('OMU', $model);
        $this->assertNull($model->OMU);
    }

    public function testUpdateColumnThatDoesNotExist()
    {
        $this->testModel->update([
            'id' => $this->modelId,
            'column_int' => 1,
            'column_does_not_exist' => 1,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($data = $this->testModel->data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertInstanceOf(stdClass::class, $model = $data[0]);

        $this->assertObjectHasProperty('id', $model);
        $this->assertSame('1', $model->id);
        $this->assertObjectHasProperty('column_int', $model);
        $this->assertSame('1', $model->column_int);
        $this->assertObjectHasProperty('column_string', $model);
        $this->assertSame('blah', $model->column_string);
        $this->assertObjectHasProperty('OCDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
        $this->assertObjectHasProperty('OMDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
        $this->assertObjectHasProperty('OCU', $model);
        $this->assertNull($model->OCU);
        $this->assertObjectHasProperty('OMU', $model);
        $this->assertNull($model->OMU);
    }

    public function testUpdateWithOnlyPrimaryKey()
    {
        $this->testModel->update([
            'id' => $this->modelId,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($data = $this->testModel->data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertInstanceOf(stdClass::class, $model = $data[0]);

        $this->assertObjectHasProperty('id', $model);
        $this->assertSame($this->modelId, $model->id);
        $this->assertObjectHasProperty('column_int', $model);
        $this->assertSame('1', $model->column_int);
        $this->assertObjectHasProperty('OCDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
        $this->assertObjectHasProperty('OMDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
        $this->assertObjectHasProperty('OCU', $model);
        $this->assertNull($model->OCU);
        $this->assertObjectHasProperty('OMU', $model);
        $this->assertNull($model->OMU);
    }

    public function testUpdateDoesNotChangeCreator()
    {
        $this->authenticate();

        $this->testModel->update([
            'id' => $this->modelId,
        ]);

        $this->assertNotError($this->testModel);
        $this->assertObjectHasProperty('data', $this->testModel);
        $this->assertIsArray($data = $this->testModel->data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertInstanceOf(stdClass::class, $model = $data[0]);

        $this->assertObjectHasProperty('id', $model);
        $this->assertSame($this->modelId, $model->id);
        $this->assertObjectHasProperty('column_int', $model);
        $this->assertSame('1', $model->column_int);
        $this->assertObjectHasProperty('OCDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
        $this->assertObjectHasProperty('OMDT', $model);
        $this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
        $this->assertObjectHasProperty('OCU', $model);
        $this->assertNull($model->OCU);
        $this->assertObjectHasProperty('OMU', $model);
        $this->assertSame('1', $model->OMU);
    }
}

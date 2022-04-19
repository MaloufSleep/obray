<?php

namespace tests\Odbo;

use stdClass;

/**
 * @covers \ODBO::update
 */
class AddTest extends OdboTestCase
{
	protected string $modelId = '1';

	protected function setUp(): void
	{
		parent::setUp();

		$this->testModel->dbh = $this->pdo;
	}

	public function testAdd()
	{
		$this->testModel->add([
			'id' => $this->modelId,
			'column_int' => 100,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame($this->modelId, $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertSame('100', $model->column_int);
		$this->assertObjectHasAttribute('OCDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
		$this->assertObjectHasAttribute('OMDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
		$this->assertObjectHasAttribute('OCU', $model);
		$this->assertSame('0', $model->OCU);
		$this->assertObjectHasAttribute('OMU', $model);
		$this->assertSame('0', $model->OMU);
	}

	public function testInsertWithOnlyPrimaryKey()
	{
		$this->testModel->add([
			'id' => $this->modelId,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame($this->modelId, $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertNull($model->column_int);
		$this->assertObjectHasAttribute('OCDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
		$this->assertObjectHasAttribute('OMDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
		$this->assertObjectHasAttribute('OCU', $model);
		$this->assertSame('0', $model->OCU);
		$this->assertObjectHasAttribute('OMU', $model);
		$this->assertSame('0', $model->OMU);
	}
	public function testAddWithoutSystemColumns()
	{
		$this->testModel->enable_system_columns = false;
		$this->testModel->add([
			'id' => $this->modelId,
			'column_int' => 100,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame($this->modelId, $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertSame('100', $model->column_int);
		$this->assertObjectNotHasAttribute('OCDT', $model);
		$this->assertObjectNotHasAttribute('OMDT', $model);
		$this->assertObjectNotHasAttribute('OCU', $model);
		$this->assertObjectNotHasAttribute('OMU', $model);
	}

	public function testInsertWithOnlyPrimaryKeyWithoutSystemColumns()
	{
		$this->testModel->enable_system_columns = false;
		$this->testModel->add([
			'id' => $this->modelId,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame($this->modelId, $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertNull($model->column_int);
		$this->assertObjectNotHasAttribute('OCDT', $model);
		$this->assertObjectNotHasAttribute('OMDT', $model);
		$this->assertObjectNotHasAttribute('OCU', $model);
		$this->assertObjectNotHasAttribute('OMU', $model);
	}
}

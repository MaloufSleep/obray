<?php

namespace tests\Odbo;

use stdClass;

/**
 * @covers ODBO::add
 */
class AddTest extends OdboTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->testModel->dbh = $this->pdo;
	}

	public function testAdd()
	{
		$this->testModel->add([
			'column_int' => 1,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame('1', $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertSame('1', $model->column_int);
		$this->assertObjectHasAttribute('OCDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
		$this->assertObjectHasAttribute('OMDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
		$this->assertObjectHasAttribute('OCU', $model);
		$this->assertSame('0', $model->OCU);
		$this->assertObjectHasAttribute('OMU', $model);
		$this->assertSame('0', $model->OMU);
	}

	public function testAddNullAttribute()
	{
		$this->testModel->add([
			'column_int' => 1,
			'column_string' => null,
		]);

		$this->assertNotError($this->testModel);
		$this->assertObjectHasAttribute('data', $this->testModel);
		$this->assertIsArray($data = $this->testModel->data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey(0, $data);
		$this->assertInstanceOf(stdClass::class, $model = $data[0]);

		$this->assertObjectHasAttribute('id', $model);
		$this->assertSame('1', $model->id);
		$this->assertObjectHasAttribute('column_int', $model);
		$this->assertSame('1', $model->column_int);
		$this->assertObjectHasAttribute('column_string', $model);
		$this->assertSame(null, $model->column_string);
		$this->assertObjectHasAttribute('OCDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OCDT);
		$this->assertObjectHasAttribute('OMDT', $model);
		$this->assertSame(date('Y-m-d H:i:s'), $model->OMDT);
		$this->assertObjectHasAttribute('OCU', $model);
		$this->assertSame('0', $model->OCU);
		$this->assertObjectHasAttribute('OMU', $model);
		$this->assertSame('0', $model->OMU);
	}
}

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

		$this->pdo->query('INSERT INTO ' . $this->testModel->table . ' (column_int) VALUES (1)');
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

	public function testUpdateWithOnlyPrimaryKey()
	{
		$this->testModel->update([
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
}

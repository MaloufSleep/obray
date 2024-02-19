<?php

namespace tests\Odbo;

/**
 * @covers \ODBO
 */
class HelperFunctionTest extends OdboTestCase
{
	public function testCommitWithNoTransactionDoesntFail()
	{
		$this->assertFalse($this->testModel->commitTransaction());
	}

	/**
	 * @throws \Exception
	 */
	public function testRollbackTransaction()
	{
		$this->testModel->startTransaction();

		$this->assertTrue($this->testModel->dbh->inTransaction());

		$this->testModel->add([
			'column_int' => 15,
		]);

		$this->assertTrue($this->testModel->rollbackTransaction());
		$this->assertFalse($this->testModel->dbh->inTransaction());

		$results = $this->pdo->query('SELECT * FROM `test_table`')->fetchAll();

		$this->assertEmpty($results);
	}

	/**
	 * @throws \Exception
	 */
	public function testCommitTransaction()
	{
		$this->testModel->startTransaction();

		$this->assertTrue($this->testModel->dbh->inTransaction());

		$this->testModel->add([
			'column_int' => 15,
		]);

		$this->assertTrue($this->testModel->commitTransaction());
		$this->assertFalse($this->testModel->dbh->inTransaction());

		$results = $this->pdo->query('SELECT * FROM `test_table`')->fetchAll();

		$this->assertCount(1, $results);

		foreach ($results as $result) {
			$this->assertArrayHasKey('id', $result);
			$this->assertSame('1', $result['id']);
			$this->assertArrayHasKey('column_int', $result);
			$this->assertSame('15', $result['column_int']);
			$this->assertArrayHasKey('OCDT', $result);
            $this->assertTimestampsEqualsWithDelta(date('Y-m-d H:i:s'), $result['OCDT']);
			$this->assertArrayHasKey('OMDT', $result);
            $this->assertTimestampsEqualsWithDelta(date('Y-m-d H:i:s'), $result['OMDT']);
			$this->assertArrayHasKey('OCU', $result);
			$this->assertSame('0', $result['OCU']);
			$this->assertArrayHasKey('OMU', $result);
			$this->assertSame('0', $result['OMU']);
		}
	}

	public function testGetOptions()
	{
		$this->testModel->getOptions(['column' => 'column_int']);
		$this->assertSame(['String at index 0'], $this->testModel->data);

		$this->testModel->getOptions(['column' => 'column_int', 'key' => 0]);
		$this->assertSame('String at index 0', $this->testModel->data);

		$this->testModel->getOptions(['column' => 'column_int', 'value' => 'String at index 0']);
		$this->assertSame(0, $this->testModel->data);
	}

	public function testSetDBConnection()
	{
		$this->assertNotSame($this->pdo, $this->testModel->dbh);
		$this->assertNull($this->testModel->reader);
		$this->testModel->setDatabaseConnection($this->pdo);
		$this->testModel->setReaderDatabaseConnection($this->pdo);
		$this->assertSame($this->pdo, $this->testModel->dbh);
		$this->assertSame($this->pdo, $this->testModel->reader);
	}

	public function testGetFirst()
	{
		$this->testModel->data = [
			[
				'column_int' => 30,
			],
			[
				'column_int' => 15,
			],
		];

		$first = $this->testModel->getFirst();

		$this->assertSame([
			'column_int' => 30,
		], $first);

		$this->testModel->data = null;

		$first = $this->testModel->getFirst();

		$this->assertSame(false, $first);

		$this->testModel->throwError('generic error here');

		$first = $this->testModel->getFirst();

		$this->assertSame(0, $first);
	}
}

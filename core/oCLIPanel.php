<?php

class oCLIPanel
{
	public $window;
	public $x;
	public $y;
	public $rows;
	public $cols;

	public function __construct($window, $x, $y, $cols, $rows)
	{
		$this->window = $window;
		$this->x = $x;
		$this->y = $y;
		$this->cols = $cols;
		$this->rows = $rows;
	}
}

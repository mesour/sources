<?php
/**
 * This file is part of the Mesour Editable (http://components.mesour.com/component/editable)
 *
 * Copyright (c) 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\Structures;

use Mesour;
use Mesour\Sources\Structures\Columns\TextColumnStructure;
use Mesour\Sources\Structures\Columns\EnumColumnStructure;
use Mesour\Sources\Structures\Columns\DateColumnStructure;
use Mesour\Sources\Structures\Columns\NumberColumnStructure;
use Mesour\Sources\Structures\Columns\IColumnStructure;
use Mesour\Sources\Structures\Columns\OneToOneColumnStructure;
use Mesour\Sources\Structures\Columns\OneToManyColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToManyColumnStructure;
use Mesour\Sources\Structures\Columns\BoolColumnStructure;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class TableStructure implements ITableStructure
{

	private $name;

	private $primaryKey = 'id';

	private $columns = [];

	public static $knownTypes = [
		IColumnStructure::DATE => DateColumnStructure::class,
		IColumnStructure::ENUM => EnumColumnStructure::class,
		IColumnStructure::NUMBER => NumberColumnStructure::class,
		IColumnStructure::BOOL => BoolColumnStructure::class,
		IColumnStructure::MANY_TO_MANY => ManyToManyColumnStructure::class,
		IColumnStructure::ONE_TO_ONE => OneToOneColumnStructure::class,
		IColumnStructure::ONE_TO_MANY => OneToManyColumnStructure::class,
		IColumnStructure::TEXT => TextColumnStructure::class,
	];

	public function __construct($name, $primaryKey = 'id')
	{
		$this->name = $name;
		$this->primaryKey = $primaryKey;
	}

	/**
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return TextColumnStructure
	 */
	public function addText($name)
	{
		return $this->addColumn($this->createColumnStructure(IColumnStructure::TEXT, $name));
	}

	/**
	 * @param string $name
	 * @return NumberColumnStructure
	 */
	public function addNumber($name)
	{
		return $this->addColumn($this->createColumnStructure(IColumnStructure::NUMBER, $name));
	}

	/**
	 * @param string $name
	 * @return DateColumnStructure
	 */
	public function addDate($name)
	{
		return $this->addColumn($this->createColumnStructure(IColumnStructure::DATE, $name));
	}

	/**
	 * @param string $name
	 * @return EnumColumnStructure
	 */
	public function addEnum($name)
	{
		return $this->addColumn($this->createColumnStructure(IColumnStructure::ENUM, $name));
	}

	/**
	 * @param string $name
	 * @return EnumColumnStructure
	 */
	public function addBool($name)
	{
		return $this->addColumn($this->createColumnStructure(IColumnStructure::BOOL, $name));
	}

	/**
	 * @return IColumnStructure[]
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	public function getColumn($name)
	{
		if (!$this->hasColumn($name)) {
			throw new Mesour\Sources\InvalidArgumentException(
				sprintf('Column with name %s not exits.', $name)
			);
		}
		return $this->columns[$name];
	}

	public function toArray()
	{
		return [
			'name' => $this->getName(),
			'primaryKey' => $this->getPrimaryKey(),
		];
	}

	public function createColumnStructure($type, $name)
	{
		if (!isset(self::$knownTypes[$type])) {
			throw new Mesour\Sources\InvalidArgumentException(
				sprintf('Type %s does not recognized.', $type)
			);
		}
		$class = self::$knownTypes[$type];
		/** @var IColumnStructure $column */
		return new $class($name);
	}

	public function hasColumn($name)
	{
		return isset($this->columns[$name]);
	}

	protected function removeColumn($name)
	{
		unset($this->columns[$name]);
	}

	protected function createColumn($class, $name)
	{
		/** @var IColumnStructure $column */
		$column = new $class($name);
		$this->addColumn($column);
		return $column;
	}

	protected function addColumn(IColumnStructure $columnStructure)
	{
		$name = $columnStructure->getName();
		if ($this->hasColumn($name)) {
			throw new Mesour\Sources\InvalidArgumentException(
				sprintf('Column with name %s already exits.', $name)
			);
		}
		$this->columns[$name] = $columnStructure;
		return $columnStructure;
	}

}

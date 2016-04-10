<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\Structures;

use Mesour;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class DataStructure extends TableStructure implements IDataStructure
{

	/**
	 * @var TableStructure[]
	 */
	protected $tableStructures = [];

	/**
	 * @var Mesour\Sources\ISource
	 */
	private $source;

	public function setSource(Mesour\Sources\ISource $source)
	{
		$this->source = $source;
		return $this;
	}

	public function getSource($need = true)
	{
		if ($need && !$this->source) {
			throw new Mesour\Sources\InvalidStateException('Source is not set.');
		}
		return $this->source;
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param string $referencedColumn
	 * @param null $pattern
	 * @return Columns\OneToOneColumnStructure
	 */
	public function addOneToOne($name, $table, $referencedColumn, $pattern = null)
	{
		$column = $this->addJoinedColumn(
			Columns\IColumnStructure::ONE_TO_ONE,
			$name,
			$table,
			$referencedColumn,
			$pattern
		);

		if (method_exists($this->getSource(), 'attachOneToOne')) {
			$this->getSource()->attachOneToOne($column, true);
		}

		return $column;
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param string $referencedColumn
	 * @param null $pattern
	 * @return Columns\ManyToOneColumnStructure
	 */
	public function addManyToOne($name, $table, $referencedColumn, $pattern = null)
	{
		$column = $this->addJoinedColumn(
			Columns\IColumnStructure::MANY_TO_ONE,
			$name,
			$table,
			$referencedColumn,
			$pattern
		);

		if (method_exists($this->getSource(), 'attachManyToOne')) {
			$this->getSource()->attachManyToOne($column, true);
		}

		return $column;
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param string $referencedColumn
	 * @param null|string $pattern
	 * @return Columns\OneToManyColumnStructure
	 */
	public function addOneToMany($name, $table, $referencedColumn, $pattern = null)
	{
		$column = $this->addJoinedColumn(
			Columns\IColumnStructure::ONE_TO_MANY,
			$name,
			$table,
			$referencedColumn,
			$pattern
		);

		if (method_exists($this->getSource(), 'attachOneToMany')) {
			$this->getSource()->attachOneToMany($column, true);
		}

		return $column;
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param string $selfColumn
	 * @param string $relationalTable
	 * @param string $relationalColumn
	 * @param null $pattern
	 * @return Columns\ManyToManyColumnStructure
	 */
	public function addManyToMany($name, $table, $selfColumn, $relationalTable, $relationalColumn, $pattern = null)
	{
		/** @var Columns\ManyToManyColumnStructure $column */
		$column = $this->addJoinedColumn(
			Columns\IColumnStructure::MANY_TO_MANY,
			$name,
			$table,
			$relationalColumn,
			$pattern
		);
		$column->setSelfColumn($selfColumn);
		$column->setReferencedTable($relationalTable);

		if (method_exists($this->getSource(), 'attachManyToMany')) {
			$this->getSource()->attachManyToMany($column, true);
		}

		return $column;
	}

	public function hasTableStructure($table)
	{
		return isset($this->tableStructures[$table]);
	}

	/**
	 * @return TableStructure[]
	 */
	public function getTableStructures()
	{
		return $this->tableStructures;
	}

	public function getTableStructure($table)
	{
		if (!isset($this->tableStructures[$table])) {
			throw new Mesour\Sources\InvalidArgumentException(
				sprintf('Table structure %s not exist. Try use method addTableToStructure on source.', $table)
			);
		}
		return $this->tableStructures[$table];
	}

	public function getOrCreateTableStructure($table, $primaryKey)
	{
		if (!isset($this->tableStructures[$table])) {
			$tableColumns = $this->getSource()->getTableColumns($table, true);
			$tableStructure = new TableStructure($table, $primaryKey);
			Mesour\Sources\Helpers::setStructureFromColumns($tableStructure, $tableColumns);

			$this->tableStructures[$table] = $tableStructure;
		}
		return $this->getTableStructure($table);
	}

	protected function addJoinedColumn($columnType, $name, $table, $referencedColumn, $pattern = null)
	{
		$tableStructure = $this->getTableStructure($table);

		/** @var Columns\BaseTableColumnStructure $column */
		if ($this->hasColumn($name)) {
			$column = $this->getColumn($name);
		} else {
			$column = $this->addColumn($this->createColumnStructure($columnType, $name));
		}

		$column->setPattern($pattern);
		$column->setReferencedColumn($referencedColumn);
		$column->setTableStructure($tableStructure);

		return $column;
	}

}

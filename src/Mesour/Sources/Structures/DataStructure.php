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
	 * @param Columns\IColumnStructure|string $referencedColumn
	 * @param string $primaryKey
	 * @return Columns\OneToOneColumnStructure
	 */
	public function addOneToOne($name, $table, $referencedColumn, $primaryKey = 'id')
	{
		if ($referencedColumn instanceof Columns\IColumnStructure) {
			$columnName = $referencedColumn->getName();
		} else {
			$columnName = $referencedColumn;
		}

		$tableStructure = $this->getOrCreateTableStructure($table, $primaryKey);
		if (!$tableStructure->hasColumn($columnName)) {
			$tableStructure->addColumn(
				$referencedColumn instanceof Columns\IColumnStructure
					? $referencedColumn
					: new Columns\TextColumnStructure($referencedColumn)
			);
		}

		/** @var Columns\OneToOneColumnStructure $column */
		if ($this->hasColumn($name)) {
			$column = $this->getColumn($name);
		} else {
			$column = $this->addColumn($this->createColumnStructure(Columns\IColumnStructure::ONE_TO_ONE, $name));
		}

		$column->setReferencedColumn($columnName);
		$column->setTableStructure($tableStructure);

		return $column;
	}

	/**
	 * @param string $name
	 * @param string $table
	 * @param null|string $referencedColumn
	 * @param null|string $pattern
	 * @return Columns\OneToManyColumnStructure
	 */
	public function addOneToMany($name, $table, $referencedColumn, $pattern = null)
	{
		$tableStructure = $this->getTableStructure($table);

		/** @var Columns\OneToManyColumnStructure $column */
		if ($this->hasColumn($name)) {
			$column = $this->getColumn($name);
		} else {
			$column = $this->addColumn($this->createColumnStructure(Columns\IColumnStructure::ONE_TO_MANY, $name));
		}

		$column->setPattern($pattern);
		$column->setReferencedColumn($referencedColumn);
		$column->setTableStructure($tableStructure);

		if (method_exists($this->getSource(), 'attachTable')) {
			$this->getSource()->attachTable($table, $referencedColumn, $name, true);
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
		$tableStructure = $this->getTableStructure($table);

		/** @var Columns\ManyToManyColumnStructure $column */
		if ($this->hasColumn($name)) {
			$column = $this->getColumn($name);
		} else {
			$column = $this->addColumn($this->createColumnStructure(Columns\IColumnStructure::MANY_TO_MANY, $name));
		}

		$column->setPattern($pattern);
		$column->setReferencedTable($relationalTable);
		$column->setReferencedColumn($relationalColumn);
		$column->setSelfColumn($selfColumn);
		$column->setTableStructure($tableStructure);

		if (method_exists($this->getSource(), 'attachManyTable')) {
			$this->getSource()->attachManyTable($column, true);
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
				sprintf('Table structure %s not exist.', $table)
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

}

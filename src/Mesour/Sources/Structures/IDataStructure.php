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
interface IDataStructure extends ITableStructure
{

	public function setSource(Mesour\Sources\ISource $source);

	/**
	 * @param bool $need
	 * @return Mesour\Sources\ISource
	 */
	public function getSource($need = true);

	/**
	 * @param $name
	 * @param $table
	 * @param Columns\IColumnStructure|string $referencedColumn
	 * @param string $primaryKey
	 * @return Columns\OneToOneColumnStructure
	 */
	public function addOneToOne($name, $table, $referencedColumn, $primaryKey = 'id');

	/**
	 * @param $name
	 * @param $table
	 * @param $referencedColumn
	 * @param null $pattern
	 * @return Columns\OneToManyColumnStructure
	 */
	public function addOneToMany($name, $table, $referencedColumn, $pattern = null);

	/**
	 * @param $name
	 * @param $table
	 * @param $selfColumn
	 * @param $relationalTable
	 * @param $relationalColumn
	 * @param null $pattern
	 * @return Columns\ManyToManyColumnStructure
	 */
	public function addManyToMany($name, $table, $selfColumn, $relationalTable, $relationalColumn, $pattern = null);

	/**
	 * @return bool
	 */
	public function hasTableStructure($table);

	/**
	 * @return TableStructure[]
	 */
	public function getTableStructures();

	/**
	 * @return TableStructure
	 */
	public function getTableStructure($table);

	/**
	 * @param string $table
	 * @param string $primaryKey
	 * @return TableStructure
	 */
	public function getOrCreateTableStructure($table, $primaryKey);

}

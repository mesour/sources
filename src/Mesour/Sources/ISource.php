<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
interface ISource
{

	/**
	 * @return string
	 */
	public function getPrimaryKey();

	/**
	 * @return string
	 */
	public function getTableName();

	/**
	 * Get total count without apply where and limit
	 * @return int
	 */
	public function getTotalCount();

	/**
	 * Add where condition
	 *
	 * @param mixed $args
	 * @return static
	 */
	public function where($args);

	/**
	 * Apply limit and offset
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return static
	 */
	public function applyLimit($limit, $offset = 0);

	/**
	 * Get count with applied where without limit
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Get data with applied where, limit and offset
	 *
	 * @return array
	 */
	public function fetchAll();

	/**
	 * Get raw data from last fetchAll()
	 *
	 * IMPORTANT! fetchAll() must be called before call this method
	 *
	 * @return array
	 */
	public function fetchLastRawRows();

	/**
	 * Get first element from data
	 *
	 * @return mixed
	 */
	public function fetch();

	/**
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function fetchPairs($key, $value);

	/**
	 * Selects columns to order by.
	 * @param string $row
	 * @param string $sorting sorting direction
	 * @return static
	 */
	public function orderBy($row, $sorting = 'ASC');

	/**
	 * @return Structures\DataStructure
	 */
	public function getDataStructure();

	public function setDataStructure(Structures\ITableStructure $dataStructure);

	public function addTableToStructure($table, $primaryKey);

	/**
	 * @param string $table
	 * @param bool $internal
	 * @return array
	 */
	public function getTableColumns($table, $internal = false);

	/**
	 * @param string $table
	 * @return static
	 */
	public function getReferencedSource($table);

}

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

    public function setPrimaryKey($primary_key);

    public function getPrimaryKey();

    /**
     * Get total count without apply where and limit
     */
    public function getTotalCount();

    /**
     * Add where condition
     *
     * @param mixed $args
     */
    public function where($args);

    /**
     * Apply limit and offset
     *
     * @param int $limit
     * @param int $offset
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
     * @param $key
     * @param $value
     * @return array
     */
    public function fetchPairs($key, $value);

    /**
     * Selects columns to order by.
     *
     * @param $row
     * @param string $sorting sorting direction
     * @return void
     */
    public function orderBy($row, $sorting = 'ASC');

    /**
     * @param $table
     * @param $column
     * @param $primaryKey
     * @return $this
     * @throws Exception
     */
    public function addReference($table, $column, $primaryKey = 'id');

    /**
     * @param $table
     * @return static
     */
    public function getReferencedSource($table);

    /**
     * @param $table
     * @return bool
     */
    public function hasReference($table);

    /**
     * @return array
     */
    public function getReferenceSettings();

}
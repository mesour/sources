<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
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
     *
     * @param String $row
     * @param String $sorting sorting direction
     * @return void
     */
    public function orderBy($row, $sorting = 'ASC');

    /**
     * @param $table
     * @param $key
     * @param $column
     * @param null $as
     * @param string $primary
     * @param bool|FALSE $left
     * @return static
     */
    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id', $left = FALSE);

    /**
     * @param $table
     * @return static
     */
    public function related($table);

    /**
     * @param $table
     * @return bool
     */
    public function isRelated($table);

    /**
     * @return array
     */
    public function getAllRelated();

}
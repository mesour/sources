<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\MesourDb;

use Mesour\Database\Connection;
use Mesour\Database\QueryBuilder\BasicQueryBuilder;
use Mesour\Sources\IActiveItem;
use Mesour\Sources\IActiveSource;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class MesourDbActiveItem implements IActiveItem
{

    /** @var BasicQueryBuilder */
    private $queryBuilder;

    public function __construct(BasicQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function where($key, $value, $operator = '=')
    {
        $this->queryBuilder->where($key . ' ' . $operator . ' ?', $value);
        return $this;
    }

    public function limit($limit)
    {
        $this->queryBuilder->limit($limit);
        return $this;
    }

    public function orderBy($order)
    {
        $this->queryBuilder->orderBy($order);
        return $this;
    }

    public function execute()
    {
        return $this->queryBuilder->getStatement()->rowCount();
    }

}
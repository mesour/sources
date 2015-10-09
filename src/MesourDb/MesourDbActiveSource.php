<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\MesourDb;

use Mesour\Components\RequiredMissingException;
use Mesour\Database\Connection;
use Mesour\Sources\IActiveSource;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class MesourDbActiveSource implements IActiveSource
{

    private $table;

    /** @var Connection */
    private $connection = NULL;

    public function __construct($table, Connection $connection = NULL)
    {
        if (is_null($connection)) {
            throw new RequiredMissingException('Mesour DB active source require Mesour connection in constructor.');
        }

        $this->table = $table;
        $this->connection = $connection;
    }

    public function insert($data)
    {
        $this->connection->query('INSERT INTO ' . $this->table, $data);

        return $this->connection->getLastInsertId();
    }

    public function update($data)
    {
        return new MesourDbActiveItem($this->connection->table($this->table)->update($data));
    }

    public function delete()
    {
        return new MesourDbActiveItem($this->connection->table($this->table)->delete());
    }

}
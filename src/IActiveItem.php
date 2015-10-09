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
interface IActiveItem
{

    public function where($key, $value, $operator = '=');

    public function orderBy($order);

    public function limit($limit);

    public function execute();

}
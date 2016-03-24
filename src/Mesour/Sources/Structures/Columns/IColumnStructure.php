<?php
/**
 * This file is part of the Mesour Editable (http://components.mesour.com/component/editable)
 *
 * Copyright (c) 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\Structures\Columns;

use Mesour;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
interface IColumnStructure
{

	const TEXT = 'text';
	const NUMBER = 'number';
	const DATE = 'date';
	const ENUM = 'enum';
	const BOOL = 'bool';
	const ONE_TO_ONE = 'one_to_one';
	const ONE_TO_MANY = 'one_to_many';
	const MANY_TO_MANY = 'many_to_many';

	public function getName();

	public function getType();

}

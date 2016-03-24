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
interface ITableStructure
{

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\TextColumnStructure
	 */
	public function addText($name);

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\NumberColumnStructure
	 */
	public function addNumber($name);

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\DateColumnStructure
	 */
	public function addDate($name);

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\EnumColumnStructure
	 */
	public function addEnum($name);

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\EnumColumnStructure
	 */
	public function addBool($name);

	/**
	 * @return Mesour\Sources\Structures\Columns\IColumnStructure[]
	 */
	public function getColumns();

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasColumn($name);

	/**
	 * @param string $name
	 * @return Mesour\Sources\Structures\Columns\IColumnStructure
	 */
	public function getColumn($name);

	/**
	 * @return array
	 */
	public function toArray();

}

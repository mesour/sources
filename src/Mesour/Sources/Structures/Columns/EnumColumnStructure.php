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
class EnumColumnStructure extends BaseColumnStructure
{

	private $values = [];

	public function addValue($key)
	{
		$this->values[$key] = $key;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return array_values($this->values);
	}

	public function toArray()
	{
		$out = parent::toArray();

		$out['values'] = $this->values;

		return $out;
	}

	public function getType()
	{
		return self::ENUM;
	}

}

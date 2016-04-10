<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
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
trait Nullable
{

	private $nullable = false;

	public function setNullable($nullable = true)
	{
		$this->nullable = (bool) $nullable;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isNullable()
	{
		return $this->nullable;
	}

}

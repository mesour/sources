<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
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
class NumberColumnStructure extends BaseColumnStructure
{

	use Mesour\Sources\Structures\Nullable;

	public function getType()
	{
		return self::NUMBER;
	}

}

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
use Mesour\Sources\Structures\TableStructure;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class OneToOneColumnStructure extends BaseTableColumnStructure
{

	public function getType()
	{
		return self::ONE_TO_ONE;
	}

}

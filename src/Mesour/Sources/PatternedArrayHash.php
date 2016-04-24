<?php
/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Mesour\Sources;

/**
 * Provides objects to work as array.
 */
class PatternedArrayHash extends ArrayHash
{

	public function __toString()
	{
		if (isset($this['_pattern'])) {
			return $this['_pattern'];
		}
		trigger_error('Key "_pattern" does not exist in data.', E_USER_WARNING);
	}

}

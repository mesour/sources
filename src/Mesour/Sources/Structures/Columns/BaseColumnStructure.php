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
abstract class BaseColumnStructure implements IColumnStructure
{

	private $name;

	private $parameters = [];

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setParameter($key, $value)
	{
		$this->parameters[$key] = $value;
		return $this;
	}

	public function getParameter($key, $default = null)
	{
		return !isset($this->parameters[$key]) ? $default : $this->parameters[$key];
	}

	public function toArray()
	{
		return [
			'name' => $this->getName(),
			'type' => $this->getType(),
			'params' => $this->parameters,
		];
	}

	abstract public function getType();

}

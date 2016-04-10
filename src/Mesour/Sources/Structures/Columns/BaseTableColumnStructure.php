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
use Mesour\Sources\Structures\TableStructure;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
abstract class BaseTableColumnStructure extends BaseColumnStructure
{

	/**
	 * @var TableStructure
	 */
	protected $tableStructure;

	private $pattern;

	public function setPattern($pattern)
	{
		$this->pattern = $pattern;
		return $this;
	}

	public function getPattern()
	{
		return $this->pattern;
	}

	public function setReferencedColumn($referencedColumn)
	{
		$this->setParameter('referenced_column', $referencedColumn);
		return $this;
	}

	public function getReferencedColumn()
	{
		if (!$this->getParameter('referenced_column')) {
			throw new Mesour\Sources\InvalidStateException('Parameter "referenced_column" is not set.');
		}
		return $this->getParameter('referenced_column');
	}

	public function setTableStructure(TableStructure $tableStructure)
	{
		$this->tableStructure = $tableStructure;
		return $this;
	}

	public function getTableStructure()
	{
		if (!$this->tableStructure) {
			throw new Mesour\Sources\InvalidStateException('TableStructure is not set.');
		}
		return $this->tableStructure;
	}

	public function toArray()
	{
		if (!$this->getParameter('referenced_column')) {
			throw new Mesour\Sources\InvalidStateException(
				'Parameter "referenced_column" is required. Use method setReferencedColumn.'
			);
		}
		if (!$this->tableStructure) {
			throw new Mesour\Sources\InvalidStateException('TableStructure is required. Use method setTableStructure.');
		}
		return array_merge(
			parent::toArray(),
			[
				'tableStructure' => $this->tableStructure->toArray(),
				'pattern' => $this->pattern,
			]
		);
	}

}

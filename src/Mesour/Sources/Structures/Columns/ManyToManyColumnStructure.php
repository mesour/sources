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
class ManyToManyColumnStructure extends BaseTableColumnStructure
{

	/**
	 * @var TableStructure
	 */
	protected $tableStructure;

	protected $referencedTable;

	protected $selfColumn;

	public function setReferencedTable($referencedTable)
	{
		$this->referencedTable = $referencedTable;
		return $this;
	}

	public function getReferencedTable()
	{
		if (!$this->referencedTable) {
			throw new Mesour\Sources\InvalidStateException('Referenced table is not set.');
		}
		return $this->referencedTable;
	}

	public function setSelfColumn($selfColumn)
	{
		$this->selfColumn = $selfColumn;
		return $this;
	}

	public function getSelfColumn()
	{
		if (!$this->selfColumn) {
			throw new Mesour\Sources\InvalidStateException('Self column is not set.');
		}
		return $this->selfColumn;
	}

	public function toArray()
	{
		if (!$this->referencedTable) {
			throw new Mesour\Sources\InvalidStateException('Referenced table is required. Use method setReferencedTable.');
		}
		if (!$this->selfColumn) {
			throw new Mesour\Sources\InvalidStateException('Self column is required. Use method setReferencedTable.');
		}
		return array_merge(
			parent::toArray(),
			[
				'referencedTable' => $this->referencedTable,
				'selfColumn' => $this->selfColumn,
			]
		);
	}

	public function getType()
	{
		return self::MANY_TO_MANY;
	}

}

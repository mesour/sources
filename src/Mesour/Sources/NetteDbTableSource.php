<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Mesour\Sources\Structures\Columns\IColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToManyColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToOneColumnStructure;
use Mesour\Sources\Structures\Columns\OneToManyColumnStructure;
use Mesour\Sources\Structures\Columns\OneToOneColumnStructure;
use Nette;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class NetteDbTableSource extends BaseSource
{

	/** @var Nette\Database\Table\Selection */
	private $netteTable;

	/** @var Nette\Database\Context */
	private $context;

	/** @var array */
	private $whereArr = [];

	/** @var integer */
	private $limit;

	/** @var integer */
	private $offset = 0;

	private $totalCount = 0;

	protected $columnMapping = [];

	public function __construct(
		$tableName,
		$primaryKey,
		Nette\Database\Table\Selection $selection,
		Nette\Database\Context $context,
		$columnMapping = []
	) {
		$this->context = $context;
		$this->netteTable = $selection;
		$this->columnMapping = $columnMapping;

		parent::__construct($tableName, $primaryKey);

		$this->totalCount = $selection->count('*');
	}

	/**
	 * @return Nette\Database\Table\Selection
	 */
	public function getTableSelection()
	{
		return $this->getSelection();
	}

	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}

	/**
	 * @param mixed $args NetteDatabase args
	 * @return $this
	 */
	public function where($args)
	{
		$this->whereArr[] = func_get_args();

		return $this;
	}

	/**
	 * Apply limit and offset
	 * @param int $limit
	 * @param int $offset
	 * @return $this
	 */
	public function applyLimit($limit, $offset = 0)
	{
		$this->limit = $limit;
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Get count after applied where
	 * @return int
	 */
	public function count()
	{
		$count = $this->getSelection()->count('*');
		$toEnd = $count - ($this->offset + $this->limit);

		return !is_null($this->limit) && $this->limit < $count
			? ($toEnd < $this->limit ? $toEnd : $this->limit)
			: $count;
	}

	/**
	 * Get searched values with applied limit, offset and where
	 * @return ArrayHash[]
	 */
	public function fetchAll()
	{
		$selection = $this->getSelection();
		$this->lastFetchAllResult = [];
		$out = [];
		foreach ($selection->fetchAll() as $row) {
			/** @var Nette\Database\Table\ActiveRow $row */
			$this->lastFetchAllResult[] = $row;

			$out[] = $row->toArray();
		}

		return $this->getWithJoinedColumns($out);
	}

	public function orderBy($row, $sorting = 'ASC')
	{
		return $this->netteTable->order($this->prefixColumn($row) . ' ' . $sorting);
	}

	/**
	 * Return first element from data
	 * @return ArrayHash|FALSE
	 */
	public function fetch()
	{
		if ($this->totalCount > 0) {
			/** @var Nette\Database\Table\ActiveRow $row */
			$row = $this->getSelection(false, false)
				->fetch();
			if (!$row) {
				return false;
			}
			$out = $this->getWithJoinedColumns([$row->toArray()]);
			return $out[0];
		} else {
			return false;
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function fetchPairs($key, $value)
	{
		return $this->getSelection()
			->select($this->prefixColumn($key))
			->select($this->prefixColumn($value))
			->fetchPairs($key, $value);
	}

	/**
	 * @param string $table
	 * @param null $callback
	 * @return static
	 */
	public function getReferencedSource($table, $callback = null)
	{
		if (!$this->context) {
			throw new InvalidStateException('For get referenced source need context in constructor.');
		}
		return parent::getReferencedSource(
			$table,
			$callback ? $callback : function () use ($table) {
				$tableStructure = $this->getDataStructure()->getTableStructure($table);
				$source = new static(
					$tableStructure->getName(),
					$tableStructure->getPrimaryKey(),
					$this->context->table($table),
					$this->context
				);
				$source->setDataStructure($tableStructure);
				return $source;
			}
		);
	}

	public function getTableColumns($table, $internal = false)
	{
		if (
			!$internal
			&& ($this->getDataStructure()->hasTableStructure($table) || $table === $this->getTableName())
		) {
			return parent::getTableColumns($table, $internal);
		}
		$columns = $this->context->getStructure()->getColumns($table);
		return $this->determineFromColumns($columns);
	}

	public function getNetteTable()
	{
		return $this->netteTable;
	}

	/**
	 * Get searched values with applied limit, offset and where
	 * @param array $ids
	 * @return ArrayHash[]
	 */
	protected function findByIds(array $ids)
	{
		if (count($ids) > 0) {
			$clone = clone $this;
			$clone->where($this->getTableName() . '.' . $this->getPrimaryKey() . ' IN (?)', $ids);
			$clone->fetchAll();
		}
		return [];
	}

	protected function getWithJoinedColumns($arr)
	{
		$ids = [];
		foreach ($arr as $item) {
			$primary = $this->getPrimaryKey();
			if (is_array($primary)) {
				$primary = reset($primary);
			}
			$ids[] = $item[$primary];
		}

		$joined = [];
		foreach ($this->getDataStructure()->getColumns() as $column) {
			if (
				$column instanceof OneToOneColumnStructure
				|| $column instanceof OneToManyColumnStructure
				|| $column instanceof ManyToOneColumnStructure
			) {
				$source = $this->getReferencedSource($column->getTableStructure()->getName());
				$source->findByIds($ids);
				$joined[$column->getName()] = $source->fetchAll();
			} elseif ($column instanceof ManyToManyColumnStructure) {
				$source = $this->getReferencedSource($column->getReferencedTable());
				$referencedTable = str_replace('_id', '', $column->getSelfColumn());
				$source->getNetteTable()->select($referencedTable . '.*');
				$source->getNetteTable()->select($column->getReferencedTable() . '.' . $column->getReferencedColumn());
				$source->where(
					$column->getReferencedTable() . '.' . $column->getReferencedColumn() . ' IN (?)',
					$ids
				);
				$joined[$column->getName()] = $source->fetchAll();
			}
		}

		$result = [];
		foreach ($arr as $key => $item) {
			$newValues = [];
			foreach ($this->getDataStructure()->getColumns() as $column) {
				$isManyToMany = $column instanceof ManyToManyColumnStructure;
				if ($column instanceof OneToManyColumnStructure || $isManyToMany) {
					$newValues[$column->getName()] = [];

					foreach ($joined[$column->getName()] as $currentItem) {
						if (
							$item[$column->getTableStructure()->getPrimaryKey()]
							=== $currentItem[$column->getReferencedColumn()]
						) {
							$forSave = (array) $currentItem;
							if ($isManyToMany) {
								unset($forSave[$column->getReferencedColumn()]);
							}
							$newValues[$column->getName()][] = $forSave;
						}
					}
					$this->addPatternToRows($column, $newValues[$column->getName()]);
				} elseif ($column instanceof OneToOneColumnStructure) {
					$newValues[$column->getName()] = [];
					foreach ($joined[$column->getName()] as $currentItem) {
						if (
							$currentItem[$column->getTableStructure()->getPrimaryKey()]
							=== $item[$column->getReferencedColumn()]
						) {
							$currentItems = [$currentItem];

							$this->addPatternToRows($column, $currentItems);
							$newValues[$column->getName()] = reset($currentItems);
							break;
						}
					}
				} elseif ($column instanceof ManyToOneColumnStructure) {
					$newValues[$column->getName()] = [];
					foreach ($joined[$column->getName()] as $currentItem) {
						if (
							$item[$column->getReferencedColumn()]
							=== $currentItem[$column->getTableStructure()->getPrimaryKey()]
						) {
							$currentItems = [$currentItem];
							$this->addPatternToRows($column, $currentItems);
							$newValues[$column->getName()] = reset($currentItems);
							break;
						}
					}
				}
			}
			$result[$key] = $this->makeArrayHash(array_merge($item, $newValues));
		}

		return $result;
	}

	protected function findColumn(array $columns, $name)
	{
		foreach ($columns as $column) {
			if ($column['name'] === $name) {
				return $column;
			}
		}
		return false;
	}

	protected function initializeDataStructure($tableName, $primaryKey)
	{
		$dataStructure = new Structures\DataStructure($tableName, $primaryKey);

		$this->setDataStructure($dataStructure);

		$structure = $this->context->getStructure();

		$columns = $structure->getColumns($this->getTableName());

		Helpers::setStructureFromColumns($dataStructure, $this->determineFromColumns($columns));

		foreach ($structure->getBelongsToReference($tableName) as $key => $table) {
			$dataStructure->getOrCreateTableStructure($table, $structure->getPrimaryKey($table));

			$targetReference = $structure->getBelongsToReference($table);
			$hasOneToOne = array_search($tableName, $targetReference);
			if ($hasOneToOne) {
				$field = $dataStructure->addOneToOne($table, $table, $key);
			} else {
				$field = $dataStructure->addManyToOne($table, $table, $key);
			}
			$column = $this->findColumn($columns, $key);
			$field->setNullable($column['nullable']);
		}

		foreach ($structure->getHasManyReference($tableName) as $table => $keys) {
			$dataStructure->getOrCreateTableStructure($table, $structure->getPrimaryKey($table));

			$sourceReference = $structure->getBelongsToReference($tableName);
			if (in_array($table, $sourceReference)) {
				continue;
			}

			$targetReference = $structure->getBelongsToReference($table);

			if (count($targetReference) > 1) {
				$match = null;
				foreach ($keys as $key => $targetTable) {
					$match = array_search($tableName, $targetReference);
					if ($match) {
						unset($targetReference[$match]);
						break;
					}
				}

				if ($match) {
					$arrayKeys = array_keys($targetReference);
					$selfColumn = reset($arrayKeys);
					$currentValue = reset($targetReference);
					$dataStructure->getOrCreateTableStructure($currentValue, $structure->getPrimaryKey($currentValue));

					$dataStructure->addManyToMany($currentValue, $currentValue, $selfColumn, $table, $match);

					continue;
				}
			}

			$match = null;
			foreach ($keys as $key => $targetTable) {
				$match = array_search($tableName, $targetReference);
				if ($match) {
					unset($targetReference[$match]);
					break;
				}
			}

			if ($match) {
				$dataStructure->addOneToMany($table, $table, $match);
			}
		}
	}

	private function determineFromColumns(array $columns)
	{
		$out = [];
		foreach ($columns as $column) {
			$type = Nette\Database\Helpers::detectType($column['nativetype']);

			if ($column['nativetype'] === 'ENUM') {
				$out[$column['name']] = [
					'type' => IColumnStructure::ENUM,
				];
				$enum = isset($column['vendor']['Type']) ? $column['vendor']['Type'] : $column['vendor']['type'];
				$options = str_getcsv(str_replace('enum(', '', substr($enum, 0, strlen($enum) - 1)), ',', "'");

				$out[$column['name']]['values'] = [];
				foreach ($options as $option) {
					$out[$column['name']]['values'][] = $option;
				}
			} elseif ($column['nativetype'] === 'TINYINT' && $column['size'] === 1) {
				$out[$column['name']] = [
					'type' => IColumnStructure::BOOL,
				];
			} else {
				switch ($type) {
					case Nette\Database\IStructure::FIELD_TEXT:
						$out[$column['name']] = [
							'type' => IColumnStructure::TEXT,
						];
						break;
					case Nette\Database\IStructure::FIELD_INTEGER:
					case Nette\Database\IStructure::FIELD_FLOAT:
						$out[$column['name']] = [
							'type' => IColumnStructure::NUMBER,
						];
						break;
					case Nette\Database\IStructure::FIELD_DATE:
					case Nette\Database\IStructure::FIELD_TIME:
					case Nette\Database\IStructure::FIELD_DATETIME:
					case Nette\Database\IStructure::FIELD_UNIX_TIMESTAMP:
						$out[$column['name']] = [
							'type' => IColumnStructure::DATE,
						];
						break;
					case Nette\Database\IStructure::FIELD_BOOL:
						$out[$column['name']] = [
							'type' => IColumnStructure::BOOL,
						];
						break;
				}
			}

			if (isset($out[$column['name']])) {
				$out[$column['name']]['nullable'] = $column['nullable'];
			}
		}
		return $out;
	}

	protected function prefixColumn($column, $newPrefix = null)
	{
		if (isset($this->columnMapping[$column])) {
			return $this->columnMapping[$column];
		}

		if (!is_null($newPrefix)) {
			return $newPrefix . '.' . $column;
		}

		return $column;
	}

	protected function getSelection($limit = true, $where = true)
	{
		$selection = clone $this->netteTable;
		if ($where) {
			foreach ($this->whereArr as $conditions) {
				call_user_func_array([$selection, 'where'], $conditions);
			}
		}
		if ($limit) {
			$selection->limit($this->limit, $this->offset);
		}

		return $selection;
	}

}

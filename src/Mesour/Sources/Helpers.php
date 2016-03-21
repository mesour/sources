<?php
/**
 * This file is part of the Mesour Editable (http://components.mesour.com/component/editable)
 *
 * Copyright (c) 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Mesour;
use Mesour\Sources\Structures\Columns;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class Helpers
{

	public static function setStructureFromColumns(Mesour\Sources\Structures\ITableStructure $structure, array $columns)
	{
		foreach ($columns as $name => $item) {
			$type = $item['type'];
			switch ($type) {
				case Columns\IColumnStructure::ENUM:
					$field = $structure->addEnum($name);
					foreach ($item['values'] as $value) {
						$field->addValue($value);
					}
					break;
				case Columns\IColumnStructure::NUMBER:
					$structure->addNumber($name);
					break;
				case Columns\IColumnStructure::DATE:
					$structure->addDate($name);
					break;
				case Columns\IColumnStructure::TEXT:
					$structure->addText($name);
					break;
				case Columns\IColumnStructure::BOOL:
					$structure->addBool($name);
					break;
			}
		}
	}

	public static function getColumnsArrayFromStructure(array $columns)
	{
		/** @var Columns\IColumnStructure[] $columns */
		$out = [];
		foreach ($columns as $column) {
			$data = [
				'type' => $column->getType(),
			];
			if ($column->getType() === Columns\IColumnStructure::ENUM) {
				$data['values'] = [];
				if (method_exists($column, 'getValues')) {
					foreach ($column->getValues() as $value) {
						$data['values'][] = $value;
					}
				}
			}
			$out[$column->getName()] = $data;
		}
		return $out;
	}

}

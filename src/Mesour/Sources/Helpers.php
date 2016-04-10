<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
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

	public static function parseValue($value, $data)
	{
		if (
			(is_array($data) || $data instanceof \ArrayAccess || is_object($data))
			&& strpos($value, '{') !== false
			&& strpos($value, '}') !== false
		) {
			return preg_replace_callback(
				'/(\{[^\{]+\})/',
				function ($matches) use ($value, $data) {
					$matches = array_unique($matches);
					$match = reset($matches);
					$key = substr($match, 1, strlen($match) - 2);
					if (is_object($data)) {
						$currentValue = isset($data->{$key}) ? $data->{$key} : '__UNDEFINED_KEY-' . $key . '__';
					} else {
						$currentValue = isset($data[$key]) ? $data[$key] : '__UNDEFINED_KEY-' . $key . '__';
					}

					return $currentValue;
				},
				$value
			);
		} else {
			return $value;
		}
	}

	public static function setStructureFromColumns(Mesour\Sources\Structures\ITableStructure $structure, array $columns)
	{
		foreach ($columns as $name => $item) {
			$type = $item['type'];
			$nullable = $item['nullable'];
			switch ($type) {
				case Columns\IColumnStructure::ENUM:
					$field = $structure->addEnum($name);
					foreach ($item['values'] as $value) {
						$field->addValue($value);
					}
					break;
				case Columns\IColumnStructure::NUMBER:
					$field = $structure->addNumber($name);
					break;
				case Columns\IColumnStructure::DATE:
					$field = $structure->addDate($name);
					break;
				case Columns\IColumnStructure::TEXT:
					$field = $structure->addText($name);
					break;
				case Columns\IColumnStructure::BOOL:
					$field = $structure->addBool($name);
					break;
			}

			if (isset($field) && method_exists($field, 'setNullable')) {
				$field->setNullable($nullable);
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

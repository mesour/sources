<?php
/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Mesour\Sources;


/**
 * Provides objects to work as array.
 */
class ArrayHash extends \stdClass implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param  array $arr to wrap
     * @param  bool $recursive
     * @return self
     */
    public static function from($arr, $recursive = TRUE)
    {
        $obj = new static;
        foreach ($arr as $key => $value) {
            if ($recursive && is_array($value)) {
                $obj->$key = static::from($value, TRUE);
            } else {
                $obj->$key = $value;
            }
        }
        return $obj;
    }

    /**
     * Returns an iterator over all items.
     * @return \RecursiveArrayIterator
     */
    public function getIterator()
    {
        return new \RecursiveArrayIterator($this);
    }

    /**
     * Returns items count.
     * @return int
     */
    public function count()
    {
        return count((array)$this);
    }

    /**
     * Replaces or appends a item.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (!is_scalar($key)) { // prevents NULL
            throw new InvalidArgumentException(sprintf('Key must be either a string or an integer, %s given.', gettype($key)));
        }
        $this->$key = $value;
    }

    /**
     * Returns a item.
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->$key;
    }

    /**
     * Determines whether a item exists.
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->$key);
    }

    /**
     * Removes the element from this list.
     * @param string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->$key);
    }

}
<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;


class Exception extends \Exception
{
}

class MissingRequiredException extends Exception
{
}

class InvalidArgumentException extends \InvalidArgumentException
{
}

class InvalidStateException extends \LogicException
{
}

class TableNotExistException extends \LogicException
{
}
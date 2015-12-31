<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Martin Procházka <juniwalk@outlook.cz>, Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Doctrine\ORM\Query;


/**
 * @author  Matouš Němec <matous.nemec@mesour.com>
 */
class DateFunction extends Query\AST\Functions\FunctionNode
{

    private $arg;

    public function getSql(Query\SqlWalker $sqlWalker)
    {
        return sprintf('DATE(%s)', $this->arg->dispatch($sqlWalker));
    }

    public function parse(Query\Parser $parser)
    {
        $parser->match(Query\Lexer::T_IDENTIFIER);
        $parser->match(Query\Lexer::T_OPEN_PARENTHESIS);

        $this->arg = $parser->ArithmeticPrimary();

        $parser->match(Query\Lexer::T_CLOSE_PARENTHESIS);
    }

}
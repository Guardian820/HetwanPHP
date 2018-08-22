<?php

/**
 * @Author: jeanw
 * @Date:   2017-11-03 23:36:55
 * @Last Modified by:   jeanw
 * @Last Modified time: 2017-12-30 20:31:03
 */

namespace Hetwan\Helper\Condition\Operator;

use NXP\Classes\Token\{AbstractOperator, TokenNumber};


class AndToken extends AbstractOperator
{
    /**
     * Regex of this operator
     * @return string
     */
    public static function getRegex()
    {
        return '\&';
    }

    /**
     * Priority of this operator
     * @return int
     */
    public function getPriority()
    {
        return 4;
    }

    /**
     * Associaion of this operator (self::LEFT_ASSOC or self::RIGHT_ASSOC)
     * @return string
     */
    public function getAssociation()
    {
        return self::LEFT_ASSOC;
    }

    /**
     * Execution of this operator
     * @param InterfaceToken[] $stack Stack of tokens
     * @return TokenNumber            Result of execution
     */
    public function execute(&$stack)
    {
        $op2 = array_pop($stack);
        $op1 = array_pop($stack);
    
        $result = (bool) $op1->getValue() && (bool) $op2->getValue();

        return new TokenNumber($result);
    }
}
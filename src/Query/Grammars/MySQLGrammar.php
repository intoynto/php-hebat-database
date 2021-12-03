<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Query\Grammars;

class MySQLGrammar extends Grammar
{
    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        return $value === '*' ? $value : '`'.str_replace('`', '``', $value).'`';
    }
}
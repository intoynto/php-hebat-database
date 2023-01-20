<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\SQLiteGrammar as Grammar;
use Intoy\HebatDatabase\Query\Processors\SQLiteProcessor;

class SQLiteConnection extends Connection
{
    /**
     * @return Grammar
     */
    protected function getDefaultGrammar()
    {
        return $this->withTablePrefix(new Grammar());
    }

    /**
     * @return SQLiteProcessor
     */
    protected function getDefaultProcessor()
    {
        return new SQLiteProcessor();
    }
}
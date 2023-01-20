<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\SqlServerGrammar as Grammar;
use Intoy\HebatDatabase\Query\Processors\SqlServerProcessor;

class SqlServerConnection extends Connection
{
    /**
     * @return Grammar
     */
    protected function getDefaultGrammar()
    {
        return $this->withTablePrefix(new Grammar());
    }

    /**
     * @return SqlServerProcessor
     */
    protected function getDefaultProcessor()
    {
        return new SqlServerProcessor();
    }
}
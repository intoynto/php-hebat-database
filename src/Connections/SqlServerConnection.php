<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\SqlServerGrammar as Grammar;
use Intoy\HebatDatabase\Schema\Grammars\SqlServerGrammar as SchemaGrammar;
use Intoy\HebatDatabase\Query\Processors\SqlServerProcessor;
use Intoy\HebatDatabase\Schema\SqlServerBuilder;

class SqlServerConnection extends Connection
{
    /**
     * @return Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new Grammar());
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Intoy\HebatDatabase\Schema\SqlServerBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SqlServerBuilder($this);
    }

     /**
     * Get the default schema grammar instance.
     *
     * @return \Intoy\HebatDatabase\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * @return SqlServerProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SqlServerProcessor();
    }
}
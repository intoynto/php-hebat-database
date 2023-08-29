<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\SQLiteGrammar as Grammar;
use Intoy\HebatDatabase\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Intoy\HebatDatabase\Query\Processors\SQLiteProcessor;
use Intoy\HebatDatabase\Schema\SQLiteBuilder;

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
     * Get a schema builder instance for the connection.
     *
     * @return \Intoy\HebatDatabase\Schema\SQLiteBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
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
     * @return SQLiteProcessor
     */
    protected function getDefaultProcessor()
    {
        return new SQLiteProcessor();
    }
}
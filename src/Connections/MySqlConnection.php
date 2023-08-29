<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use PDO;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\MySQLGrammar as Grammar;
use Intoy\HebatDatabase\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Intoy\HebatDatabase\Query\Processors\MySqlProcessor;
use Intoy\HebatDatabase\Schema\MySqlBuilder;

class MySqlConnection extends Connection
{
    /**
     * Determine if the connected database is a MariaDB database.
     *
     * @return bool
     */
    public function isMaria()
    {
        return strpos($this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), 'MariaDB') !== false;
    }

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
     * @return \Intoy\HebatDatabase\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
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
     * @return MySqlProcessor
     */
    protected function getDefaultProcessor()
    {
        return new MySqlProcessor();
    }
}
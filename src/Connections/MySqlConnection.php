<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use PDO;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\MySQLGrammar as Grammar;
use Intoy\HebatDatabase\Query\Processors\MySqlProcessor;

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
     * @return MySqlProcessor
     */
    protected function getDefaultProcessor()
    {
        return new MySqlProcessor();
    }
}
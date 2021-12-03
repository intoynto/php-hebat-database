<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connections;

use PDO;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Query\Grammars\PostgresGrammar as Grammar;
use Intoy\HebatDatabase\Query\Processors\PostgresProcessor;

class PostgresConnection extends Connection
{
    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement  $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $pdoParam = PDO::PARAM_INT;
            } elseif (is_resource($value)) {
                $pdoParam = PDO::PARAM_LOB;
            } else {
                $pdoParam = PDO::PARAM_STR;
            }

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $pdoParam
            );
        }
    }

    /**
     * @return Grammar
     */
    protected function getDefaultGrammar()
    {
        return $this->withTablePrefix(new Grammar());
    }


    /**
     * @return PostgresProcessor
     */
    protected function getDefaultProcessor()
    {
        return new PostgresProcessor();
    }
}
<?php

namespace Intoy\HebatDatabase\Query\Processors;

use Intoy\HebatDatabase\Query\Builder;

class PostgresProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */    
    // ===========================
    // SCRIPT MODIFY
    // ===========================
    /*
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        //$connection->recordsHaveBeenModified();

        $result = $connection->selectFromWriteConnection($sql, $values)[0];

        $sequence = $sequence ?: 'id';

        $id = is_object($result) ? $result->{$sequence} : $result[$sequence];

        return is_numeric($id) ? (int) $id : $id;
    }
    */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();
        //$connection->recordsHaveBeenModified();
        $result = $connection->selectFromWriteConnection($sql, $values)[0];
        $sequence = $sequence ?: 'id';

        $found_prop=false;
        if(is_object($result))
        {
            if(isset($result->$sequence))
            {
                $id=$result->{$sequence};
                $found_prop=true;
            }
        }
        elseif(is_array($result))
        {
            $columns=array_keys($result);
            if(in_array($sequence,$columns))
            {
                $id=$result[$sequence];
                $found_prop=true;
            }
        }

        //$id = is_object($result) ? $result->{$sequence} : $result[$sequence];
        if(!$found_prop)
        {
            // get pdo last insert id
            $id=$connection->getPdo()->lastInsertId();
        }

        return is_numeric($id) ? (int) $id : $id;
    }
    

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->column_name;
        }, $results);
    }
}

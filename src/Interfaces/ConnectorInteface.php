<?php

namespace Intoy\HebatDatabase\Interfaces;

use Closure;
use Intoy\HebatDatabase\Query\Builder;
use Intoy\HebatDatabase\Query\Expression;


interface ConnectorInteface 
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param  \Closure|Builder|string  $table
     * @param  string|null  $as
     * @return Builder
     */
    public function table($table, $as = null);


    /**
     * Get a new raw query expression.
     *
     * @param  mixed  $value
     * @return Expression
     */
    public function raw($value);

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true);


    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true);


    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function insert($query, $bindings = []);


    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function update($query, $bindings = []);


    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);


    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings);


    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1);

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction();


    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit();


    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack();
}
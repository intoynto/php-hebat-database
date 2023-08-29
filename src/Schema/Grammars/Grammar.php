<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Schema\Grammars;

use Intoy\HebatDatabase\Grammar as BaseGrammar;
use Intoy\HebatDatabase\Connection;

abstract class Grammar extends BaseGrammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * The commands to be executed outside of create or alter command.
     *
     * @var array
     */
    protected $fluentCommands = [];


    /**
     * Compile a create database command.
     *
     * @param  string  $name
     * @param  Connection  $connection
     * @return void
     *
     * @throws \LogicException
     */
    public function compileCreateDatabase($name, $connection)
    {
        throw new \LogicException('This database driver does not support creating databases.');
    }

    /**
     * Compile a drop database if exists command.
     *
     * @param  string  $name
     * @return void
     *
     * @throws \LogicException
     */
    public function compileDropDatabaseIfExists($name)
    {
        throw new \LogicException('This database driver does not support dropping databases.');
    }
}
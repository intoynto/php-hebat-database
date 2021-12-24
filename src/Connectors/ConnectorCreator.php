<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Connectors;

use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Connections\{
    MySqlConnection,
    PostgresConnection,
    SqlServerConnection,
    SQLiteConnection,
    Oci8Connection,
};
use Intoy\HebatSupport\Arr;

class ConnectorCreator 
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string|null  $name
     * @return Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);
        return $this->createSingleConnection($config);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param  array  $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
     *
     * @param  array  $config
     * @return Connection
     */
    protected function createSingleConnection(array $config)
    {
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
    }


    /**
     * Create a new Closure that resolves to a PDO instance.
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolver(array $config)
    {
        return $this->createPdoResolverWithoutHosts($config);
    }


    /**
     * Create a new Closure that resolves to a PDO instance where there is no configured host.
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithoutHosts(array $config)
    {
        return function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
    }


    /**
     * Create a new connection instance.
     *
     * @param  string  $driver
     * @param  \PDO|\Closure  $connection
     * @param  string  $database
     * @param  string  $prefix
     * @param  array  $config
     * @return Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) 
        {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
            case 'oracle':
                return new Oci8Connection($connection, $database, $prefix, $config);
        }

        throw new \InvalidArgumentException("Unsupported driver [{$driver}].");
    }


    /**
     * @param array $config
     * @return MySqlConnector|SqlServerConnector|PostgresConnector|SQLiteConnector
     * @throws \InvalidArgumentException
     */
    public static function createConnector(array $config)
    {
        if(!isset($config['driver']))
        {
            throw new \InvalidArgumentException('A driver must b specified.');
        }

        switch($config['driver'])
        {
            case 'mysql':return new MySqlConnector;
            case 'sqlsrv': return new SqlServerConnector;
            case 'pgsql':return new PostgresConnector;
            case 'sqllite':return new SQLiteConnector;
            case 'oracle':return new OracleConnector;
        }

        throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}].");
    }
}
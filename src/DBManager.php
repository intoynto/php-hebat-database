<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase;

use Intoy\HebatDatabase\Connectors\ConnectorCreator as Factory;
use Intoy\HebatDatabase\Interfaces\ConnectionResolverInterface;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\ConfigurationParser;
use Intoy\HebatDatabase\Model;
use Intoy\HebatSupport\Arr;
use Intoy\HebatSupport\Str;

class DBManager implements ConnectionResolverInterface
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * Active connection instance
     * @var array
     */
    protected $connections=[];


    /**
     * Config Array configuration connection
     * @var array 
     */
    protected $config=[];


    /**
     * The callback to be executed to reconnect to a database.
     *
     * @var callable
     */
    protected $reconnector;


    /**
     * @param array $config
     * @param Factory $factory
     */
    public function __construct($config, ?Factory $factory=null)
    {
        $this->config=$config;
        $this->factory=$factory?:new Factory();
        $this->reconnector=function ($connection){ $this->reconnect($connection->getName()); };
    }

    public function getDefaultConnection()
    {
        return Arr::get($this->config,"default");
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->config["default"]=$name;
    }

    /**
     * Koneksikan koneksi berdasarkan nama config
     * @param string|null $name
     * @return Connection
     */
    public function reconnect($name=null)
    {
        $this->disconnect($name=$name?:$this->getDefaultConnection());
        if(isset($this->connections[$name]))
        {
            return $this->connection($name);
        }

        return $this->refreshPdoConnection($name);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string|null  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }


    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return Connection
     */
    public function connection($name = null)
    {
        [$database,$type]=$this->parseConnectionName($name);
        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }


    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        return $this->factory->make($config, $name);
    }


    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
        ? explode('::', $name, 2) : [$name, null];
    }


    /**
     * Prepare the database connection instance.
     *
     * @param  Connection  $connection
     * @param  string  $type
     * @return Connection
     */
    protected function configure(Connection $connection, $type)
    {
        $connection = $this->setPdoForType($connection, $type);

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
        $connection->setReconnector($this->reconnector);

        return $connection;
    }


    /**
     * Prepare the read / write mode for database connection instance.
     *
     * @param  Connection  $connection
     * @param  string|null  $type
     * @return Connection
     */
    protected function setPdoForType(Connection $connection, $type = null)
    {
        return $connection;
    }



    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();    

        if (is_null($config = Arr::get($this->config, $name))) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return (new ConfigurationParser())
            ->parseConfiguration($config);
    }


    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string  $name
     * @return Connection
     */
    protected function refreshPdoConnection($name)
    {
        [$database, $type] = $this->parseConnectionName($name);

        $fresh = $this->configure($this->makeConnection($database),$type);

        return $this->connections[$name]
            ->setPdo($fresh->getRawPdo())
            ;
    }


    /**
     * Bool Model Global
     * @return void
     */
    public function bootModel()
    {
        Model::setConnectionResolver($this);
    }
}
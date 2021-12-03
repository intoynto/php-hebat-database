<?php

namespace Intoy\HebatDatabase\Tools\Postgree;

use Exception;
use PDO;

class PgDump 
{
    protected $driver='pgsql';
    protected $host='localhost';
    protected $port=5432;
    protected $db_user='postgres';
    protected $db_password='';
    protected $db_name='';

    /**
     * @var PDO
     */
    protected $DB;    

    protected function envPut()
    {
        if($this->db_password)
        {
            putenv('PGPASSWORD='.$this->db_password);
        }
        putenv('PGUSER='.$this->db_user);
        putenv('PGHOST='.$this->host);
        putenv('PGPORT='.$this->port);
        putenv('PGDATABASE='.$this->db_name);
    }


    function setDriver(string $driver):PgDump
    {
        $this->driver=$driver;
        return $this;
    }

    function setHost(string $host):PgDump
    {
        $this->host=$host;
        return $this;
    }

    function setUser(string $db_user):PgDump
    {
        $this->db_user=$db_user;
        return $this;
    }

    function setPassword(string $db_password):PgDump
    {
        $this->db_password=$db_password;
        return $this;
    }

    function setDatabase(string $db_name):PgDump
    {
        $this->db_name=$db_name;
        return $this;
    }

    protected function getIsWindows():bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    function start(string $filename)
    {
        $dsn=$this->driver.":host=".$this->host.';dbname='.$this->db_name;
        $this->DB=new PDO($dsn,$this->db_user,$this->db_password);

        $DB=$this->DB;

        $k="SELECT * FROM pg_settings WHERE name='data_directory' LIMIT 1";
        $s=$DB->prepare($k);
        $s->execute();
        $test=$s->fetch(PDO::FETCH_ASSOC);   
        $dir_installation=trim($test['setting']);
        $dir_installation=str_replace('/',DIRECTORY_SEPARATOR,$dir_installation);
        $dir_bin=dirname($dir_installation).DIRECTORY_SEPARATOR."bin".DIRECTORY_SEPARATOR;

        $isWindows=$this->getIsWindows();

        // jika bukan windows = IS UNIX
        if(!$isWindows)
        {
            $dir_bin='/usr/bin/';
        }

        $exfilename=explode(".",$filename);
        $ext=end($exfilename);       
        $ext=trim(strtoupper($ext));

        $format="custom";
        if($ext==="SQL")
        {
            $format="plain";
        }     
        
        $firstCommandPath='"'.$dir_bin.'pg_dump';
        if($isWindows){
            $firstCommandPath.='.exe';
        }        
        $firstCommandPath.='"'; //tutup kutip

        $command=$firstCommandPath.' --host '.$this->host.' --port '.$this->port.' --username '.$this->db_user;           
        $command.=' --format '.$format;
        $command.=' --file '.$filename;        

        $this->envPut();
        exec($command);

        if(!file_exists($filename))
        {
            throw new Exception('Can"t execute '.$firstCommandPath." of ".$command.' using OS '.($isWindows?'Windows':'Unix').'.');
        }
    }


    function __construct(
        string $driver='pgsql',
        string $host='localhost',
        int    $port=5432,
        string $database='test',
        string $username='postgres',
        string $password=''
    )
    {
        $this->driver=$driver;
        $this->host=$host;
        $this->port=$port;
        $this->db_user=$username;
        $this->db_password=$password;
        $this->db_name=$database;
    }
}
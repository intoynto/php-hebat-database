<?php

namespace Intoy\HebatDatabase;

use Intoy\HebatDatabase\Query\Builder;
use Intoy\HebatDatabase\Query\Expression;
use Intoy\HebatDatabase\Traits\GuardAttributes;
use Intoy\HebatDatabase\Traits\HasAttributes;
use Intoy\HebatDatabase\Connection;
use Intoy\HebatDatabase\Interfaces\ConnectionResolverInterface;
use Intoy\HebatDatabase\Interfaces\ConnectionResolverInterface as Resolver;
use Intoy\HebatSupport\Arr;
use Intoy\HebatSupport\Str;

abstract class Model 
{
    use GuardAttributes;
    use HasAttributes;

    /**
     * @var string data name
     */
    protected $name="Entitas";

    /**
     * @var string $table name
     */
    protected $table;

    /**
     * @var string $view name
     */
    protected $view;

    /**
     * @var array $fields of table
     */
    protected $fields;

    /**
     * @var array $fields of table
     */
    protected $view_fields;

    /**
     * @var string[] 
     * contoh [nama_request=>target_field]
     * target field bisa string atau array
     */
    protected $fields_search_string=[];

    /**
     * @var string[]
     * contoh [nama_request=>target_field]
     * target field bisa string atau array
     */
    protected $fields_search_int=[];

    /**
     * @var string[]
     * contoh [nama_request=>target_field]
     * target field bisa string atau array
     * konidisi WHERE | AND
     */
    protected $fields_where_string=[];


    /**
     * @deprectated
     * @var array $relations array(tableName=>array(field="field","desc"=>"desc"))
     */
    protected $relations=[];


    /**
     * @var array array of string ModelClass
     */
    protected $model_relations=[];


    /**
     * The connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection;


    /**
     * @param array
     */
    public function __construct(array $attributes=[])
    {
        if(count($attributes)>0)
        {
            $this->fill($attributes);
        }
    }

    /**
     * @return string nama data / entitas
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array<string>
     */
    public function getModelRelations():array
    {
        return $this->model_relations;
    }

    /**
     * Fillable attibute dari array attribute
     * @param array $attributes
     */
    public function fill(array $attributes)
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            // The developers may choose to place some attributes in the "fillable" array
            // which means only those attributes may be set through mass assignment to
            // the model, and all others will just get ignored for security reasons.
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } 
        }
    }


    /**
     * Decorator Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        if(is_array($this->fillable) && count($this->fillable)>0) return $this->fillable;

        return $this->fields??[];
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }


    /**
     * Get the connection resolver instance.
     *
     * @return ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }


    /**
     * @param string|null $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connection=$name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connection;
    }


    /**
     * Get database connection 
     * @return Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }


    /**
     * Resolve a connection instance.
     *
     * @param  string|null  $connection
     * @return Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }


    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }


    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * @return string|string[]
     */
    public function getFields()
    {
        return !empty($this->fields)?$this->fields:['*'];
    }


    public function getTable()
    {
        $class_basename=function($class){
            $class = is_object($class) ? get_class($class) : $class;
            return basename(str_replace('\\', '/', $class));
        };
        $table=$this->table??strtolower($class_basename($this));
        $table=Str::endsWith($table,'model')?Str::substr($table,0,strlen($table)-strlen('model')):$table;       
        return $table;
    }

    /**
     * @return array[0,1]  0=tableOrView, 1=fields atau viewFields
     */
    public function getTableOrView()
    {
        $table=isset($this->view) && !empty($this->view)?$this->view:$this->getTable();
        $fields=$this->getFields();
        if(!empty($this->view_fields))
        {
            $fields=array_merge($fields,$this->view_fields);
        }
        return [$table,$fields];
    }

    /**
     * @return Connection
     */
    public static function connection()
    {
        return (new static())->getConnection();
    }


    /**
     * ========= Static Public Resolvable =============
     */

    /**
     * Add a basic where clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return Builder
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $obj = new static;
        $con=$obj->getConnection();   
        if(is_array($column))     
        {
            return $con->table($obj->getTable())->where($column);
        }
        return $con->table($obj->getTable())->where($column, $operator, $value, $boolean);
    }


    /**
     * Add a basic where clause to the query.
     *
     * @param  string $valueExpresion
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return Builder
     */
    public static function whereEx(string $valueExpresion, $operator = null, $value = null, $boolean = 'and')
    {
        $obj = new static;
        $con=$obj->getConnection();        
        return $con->table($obj->getTable())->where(new Expression($valueExpresion), $operator, $value, $boolean);
    }


    /**
     * Where from fillable      
     * @param array $attributes array of array("field"=>"value") join type "AND"
     * @return Builder
     */
    public static function whereFill(array $attributes)
    {
        $obj = new static;
        $con=$obj->getConnection();    
        $stub=$con->query()->select($obj->getFields()?:"*")->from($obj->getTable());

        foreach($attributes as $f => $val)
        {
            $stub=$stub->where($f,"=",$val);
        }

        return $stub;
    }


    /**
     * Where from fillable   
     * @param Builder $builder   
     * @param array $attributes array of array("field"=>"value") join type "AND"
     */
    public static function whereFillQuery(Builder &$builder, array $attributes)
    {
        foreach($attributes as $f => $val)
        {
            $builder=$builder->where($f,"=",$val);
        }
    }

    /**
     * @param array $attribut
     */
    public static function create(array $attributes)
    {
        $obj=new static($attributes);

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $obj->getAttributesForInsert();


        if (empty($attributes)) {
            return true;
        }

        $builder=$obj->getConnection()->query();
        $builder->from($obj->getTable())->insert($attributes);
        return $attributes;        
    }


    /**
     * @param array $attributes
     * @param string|null  $sequence
     * @return int
     */
    public static function createGetId(array $attributes, $sequence = null)
    {
        $obj=new static($attributes);

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $obj->getAttributesForInsert();


        if (empty($attributes)) {
            return true;
        }

        $builder=$obj->getConnection()->query();
        return $builder->from($obj->getTable())->insertGetId($attributes,$sequence);
    }

    /**
     * @return Builder
     */
    public static function from()
    {
        $obj=new static();
        return $obj->getConnection()->query()->from($obj->getTable());
    }


    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributeWhere
     * @param  array  $values
     * @return bool
     */
    public static function updateOrInsert(array $attributeWhere, array $values = [])
    {
        $obj=new static();
        $con=$obj->getConnection();  
        return $con->query()->from($obj->getTable())->updateOrInsert($attributeWhere,$values);
    }


    /**
     * @return mixed
     */
    public static function first($column,$value=null,$operator="=")
    {
        $obj = new static;
        $con=$obj->getConnection();  
        return $con->table($obj->getTable())->where($column,$operator,$value)->first();
    }


    /**
     * @param array $attributes
     * @return Model|null
     */
    public static function getRelation(array $attributes)
    {
        $obj=new static;
        $createModel=function(string $class):Model  { return new $class(); };
        foreach($obj->getModelRelations() as $class)
        {
            $mod=$createModel($class);
            $fillable=$mod->getFillable();

            if(count($fillable)<1) continue;

            $fields=[];
            $stub=$obj->connection()->query()->from($mod->getTable());  

            foreach($attributes as $field => $value)
            {
                if(in_array($field,$fillable)){

                    $mod->$field=$value;
                    $stub->where($field,"=",$value);
                    $fields[]=$field;               
                }
            }  
            
            if(count($fields)>0 && $stub->limit(1)->exists())
            {
                return $mod;
            }
        }
        return null;
    }


    /**    
     * @param array $param
     * @param array $options ['limit'=>20,'order'=>[],'direction'=>'asc|desc']
     * @return mixed
     */
    public static function queryParams(array $params, array $options=[])
    {
        $obj=new static;
        [$table,$fields]=$obj->getTableOrView();
        return static::queryParamsFrom($obj,$table,$fields,$params,$options);
    }

    /**
     * Generater query params
     * @param Model $model
     * @param string $table
     * @param array $fields
     * @param array $param
     * @param array $options ['limit'=>20,'order'=>[],'direction'=>'asc|desc']
     * @return mixed
     */
    public static function queryParamsFrom($model, $table, $fields, array $params, array $options=[])
    {
        $obj=$model;   
        $con=$obj->getConnection();
        $builder=$con->query();
        $builder->select($fields)
            ->from($table);
        return static::queryParamsFromBuilder($builder,$obj,$params, $options);
    }

    /**
     * Generater query params
     * @param Model $model
     * @param array $param
     * @param array $options ['limit'=>20,'order'=>[],'direction'=>'asc|desc']
     * @return mixed
     */
    public static function queryParamsFromBuilder(Builder $builder, $model, array $params, array $options=[])
    {
        $obj=$model;  
        $con=$obj->connection();

        collect($obj->fields_search_string)->map(function($val,$key) use ($params,$builder)
        {
            $value_param=Arr::get($params,$key);
            if(empty($value_param)) return;

            $value_param=strtoupper((string)$value_param);
            if(is_array($val))
            {
                $expresions=[];
                foreach(array_values($val) as $key => $f)
                {
                    $expresions[]=new Expression("upper({$f})");
                }
                $builder->whereFields($expresions,'like',"%{$value_param}%");
            }
            else {
                if($value_param)
                {
                    $expresion=new Expression("upper({$val})");    
                    $builder->where($expresion,"like","%{$value_param}%");
                }
            }
        });

        collect($obj->fields_where_string)->map(function($field,$key) use ($params,$builder)
        {
            $value_field=Arr::get($params,$key);
            if(empty($value_field)) return;

            if(is_array($value_field))
            {
                $values=[];
                foreach(array_values($value_field) as $set)
                {
                    if(!empty($set))
                    {
                        $set=strtoupper((string)$set);
                        $values[]=$set;
                    }       
                }
                $expresion=new Expression("upper({$field})"); 
                $builder->whereIn($expresion,$values);
            }          
            else {
                if($value_field)
                {
                    $value_field=strtoupper((string)$value_field);       
                    $expresion=new Expression("upper({$field})");    
                    $builder->where($expresion,"=",$value_field);
                }
            }
            
        });

        collect($obj->fields_search_int)->map(function($val,$key) use ($params,$builder)
        {
            $value_param=Arr::get($params,$key);
            $is_empty=$value_param===null || $value_param==='';

            if($is_empty) return;

            if(is_array($val))
            {
                foreach(array_values($val) as $f){
                    $builder->where($f,'=',$value_param);
                }
            }
            else {
                if(is_array($value_param))
                {
                    $builder->whereIn($val,$value_param);
                }
                else 
                {
                    $builder->where($val,"=",$value_param);
                }
            }
        });

        $order=Arr::get($options,'order');
        if(!empty($order))
        {
            $order=is_array($order)?$order:[$order];
            $order=array_values($order);
            $direction=(string)Arr::get($options,'direction');
            $direction=strtolower($direction)==='desc'?$direction:'asc';
            foreach($order as $f)
            {
                $builder->orderBy($f,$direction);
            }
        }

        $offset=0;
        $useLimit=true;
        $limit=null;

        $tOptions=isset($options["limit"])?$options:$params;

        if(isset($tOptions['limit']))
        {
            $limit=Arr::get($tOptions,"limit");

            if(!is_null($limit))
            {
                $limit=(int)$limit;                
            }

            if($limit===0 || $limit===null)
            {
                $useLimit=false;
            }
        }
        else {
            $limit=20;
            $useLimit=true;
        }
       
        $page=Arr::get($params,'page',1);
        $page=(int)$page; 
        $page=$page<1?1:$page;
        $pagecount=1;
        $jumlah_data=0;
        $use_count_record=true;
        
        if($useLimit)
        {
            $use_count_record=false;
            $clone=$builder->clone();
            $jumlah_data=$clone->count();
            $offset=($page-1) * $limit;
            $pagecount=$jumlah_data>0?ceil($jumlah_data/$limit):1;
            $pagecount=$pagecount<1?1:$pagecount;
            $builder->limit($limit)->offset($offset);
        }
        
        $query=$builder->toSql();
        $bindings=$builder->getBindings();

        $records=$con->select($query,$bindings);
        $jumlah_data=$use_count_record?count($records):$jumlah_data;

        $results=[
            'page'=>$page,
            'pagecount'=>$pagecount,
            'limit'=>$limit,
            'rowcount'=>count($records),
            'totalrow'=>$jumlah_data,
            'records'=>$records,
        ];
        return (object)$results;
    }
}
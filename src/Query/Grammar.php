<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase\Query;

abstract class Grammar
{
    /**
     * prefix
     * @var string
     */
    protected $tablePrefix='';


    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [];


    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [];



    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array  $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    /**
     * Wrap value
     * @param Expression|string $value
     * @param bool $prefixAlias
     * @return string
     */
    public function wrap($value,$prefixAlias=false)
    {
        
        if($this->isExpression($value))
        {
            return $this->getValue($value);
        }

        // jika value wrap mengandung alias kolom 
        if(stripos($value,' as ')!==false)
        {
            return $this->wrapAliasedValue($value,$prefixAlias);
        }

        return $this->wrapSegments(explode('.',$value));
    }


    /**
     * Wrap a value has alias
     * @param string $value
     * @param bool $prefixAlias
     * @return string
     */
    protected function wrapAliasedValue($value,$prefixAlias=false)
    {
        $segments=preg_split('/\s+as\s+/i', $value);

        // jika wraping table tidak mengandung prefix dalam alias
        if($prefixAlias)
        {
            $segments[1]=$this->tablePrefix.$segments[1];
        }

        return $this->wrapSegments(explode('.',$segments[0])).' as '.$this->wrapValue($segments[1]);
    }


    /**
     * Wrap value segments
     * @param array $segments
     * @return string
     */
    protected function wrapSegments($segments)
    {        
        return collect($segments)->map(function($segment,$key) use($segments){
            return $key==0 && count($segments)>1
            ?$this->wrapTable($segment)
            :$this->wrapValue($segment);
        })->implode('.');
    }


    /**
     * Wrap table dalam keyword
     * @param Expression|string $table
     * @return string
     */
    public function wrapTable($table)
    {
        if(!$this->isExpression($table))
        {
            return $this->wrap($this->tablePrefix.$table,true);
        }

        return $this->getValue($table);
    }

    /**
     * Wrap a single string in keyword identifers
     * @param string $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if($value!=='*')
        {
            return '"'.str_replace('"','""',$value).'"';
        }
        return $value;
    }


    /**
     * Wrap a union subquery in parentheses.
     *
     * @param  string  $sql
     * @return string
     */
    protected function wrapUnion($sql)
    {
        return '(' . $sql . ')';
    }


    /**
     * Determine if expresion
     * @param mixed $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }


    /**
     * Get the value of a raw expresion
     * @param Expression|string $value
     * @return string
     */
    public function getValue($value)
    {
        return $value instanceof Expression?$value->getValue():$value;
    }


    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }


    /**
     * Compile a select query into SQL.
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $builder)
    {
        if($builder->unions && $builder->aggregate)
        {
            return $this->compileUnionAggregate($builder);
        }

        // if query does not have any columns
        $original=$builder->columns;

        if(is_null($builder->columns))
        {
            $builder->columns=['*'];
        }

        // to compile query        
        $sql=trim($this->concatenate($this->compileComponents($builder)));

        if($builder->unions)
        {
            $sql=$this->wrapUnion($sql).' '.$this->compileUnions($builder);
        }

        $builder->columns=$original;

        return $sql;
    }


    /**
     * Compile a union aggregate query into SQL.
     *
     * @param  Builder  $query
     * @return string
     */
    protected function compileUnionAggregate(Builder $query)
    {
        $sql = $this->compileAggregate($query, $query->aggregate);

        $query->aggregate = null;

        return $sql.' from ('.$this->compileSelect($query).') as '.$this->wrapTable('temp_table');
    }


    /**
     * Compile agregat
     * @param Builder
     * @param array $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query,$aggregate)
    {
        $column=$this->columnize($aggregate['columns']);

        // if query has a "distrinct"
        if(is_array($query->distinct))
        {
            $column='distinct '.$this->columnize($query->distinct);
        } elseif($query->distinct && $column!=='*')
        {
            $column='distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate ';
    }


    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array  $segments
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }


    /**
     * Compile components
     * @param Builder $query
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $sql=[];
        foreach($this->selectComponents as $component)
        {
            if(isset($query->$component))
            {
                $method='compile'.ucfirst($component);
                $sql[$component]=$this->$method($query,$query->$component);
            }
        }
        return $sql;
    }


    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param  Builder  $builder
     * @return string
     */

     protected function compileUnions(Builder $builder)
    {
        $sql = '';

        foreach ($builder->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (! empty($builder->unionOrders)) {
            $sql .= ' '.$this->compileOrders($builder, $builder->unionOrders);
        }

        if (isset($builder->unionLimit)) {
            $sql .= ' '.$this->compileLimit($builder, $builder->unionLimit);
        }

        if (isset($builder->unionOffset)) {
            $sql .= ' '.$this->compileOffset($builder, $builder->unionOffset);
        }

        return ltrim($sql);
    }


    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */

    protected function compileUnion(array $union)
    {
        $conjunction = $union['all'] ? ' union all ' : ' union ';

        return $conjunction.$this->wrapUnion($union['query']->toSql());
    }


    /**
     * Compile the "order by" portions of the query.
     *
     * @param  Builder  $builder
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(Builder $builder, $orders)
    {
        if (! empty($orders)) {
            return 'order by '.implode(', ', $this->compileOrdersToArray($builder, $orders));
        }

        return '';
    }


    /**
     * Compile the "limit" portions of the query.
     *
     * @param  Builder  $builder
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $builder, $limit)
    {
        return 'limit '.(int) $limit;
    }


   /**
     * Compile the "offset" portions of the query.
     *
     * @param  Builder  $builder
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $builder, $offset)
    {
        return 'offset '.(int) $offset;
    }


    /**
     * Compile the query orders to an array.
     *
     * @param  Builder  $builder
     * @param  array  $orders
     * @return array
     */
    protected function compileOrdersToArray(Builder $builder, $orders)
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->wrap($order['column']).' '.$order['direction'];
        }, $orders);
    }
    

    /**
     * Get Operators
     * @return array
     */
    public function getOperators(){ return $this->operators; }


    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }


    /**
     * Create query parameter place-holders for an array.
     *
     * @param  array  $values
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }


    /**
     * Get the grammar's table prefix.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }
}
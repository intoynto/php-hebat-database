<?php

declare (strict_types=1);

namespace Intoy\HebatDatabase\Query;

use stdClass;
use Intoy\HebatDatabase\Query\Builder;
use Intoy\HebatDatabase\Query\Expression;
use Intoy\HebatSupport\Arr;

class QueryHelper 
{
    /**
     * @param Builder $builder
     * @param array $requestParams
     * @param string $requestField
     * @param string|array $fields
     * @return void
     */
    public static function addSearchString($builder,$requestParams,$requestField,$fields)
    {
        if(empty($requestField) || empty($fields)) return;

        $value_param=Arr::get($requestParams,$requestField);
        if(empty($value_param)) return;

        $value_param=strtoupper((string)$value_param);
        if(is_array($fields))
        {
            $expresions=[];
            foreach(array_values($fields) as $key => $f)
            {
                $expresions[]=new Expression("upper({$f})");
            }
            $builder->whereFields($expresions,'like',"%{$value_param}%");
        }
        else {
            if($value_param)
            {
                $expresion=new Expression("upper({$fields})");    
                $builder->where($expresion,"like","%{$value_param}%");
            }
        }
       
    }


    /**
     * @param Builder $builder
     * @param array $requestParams
     * @param string $requestField
     * @param string|array $fields
     * @return void
     */
    public static function addWhereString($builder,$requestParams,$requestField,$fields)
    {
        if(empty($requestField) || empty($fields)) return;

        $value_param=Arr::get($requestParams,$requestField);

        if(empty($value_param)) return;

        $value_param=strtoupper((string)$value_param);
        if(is_array($fields))
        {
            foreach(array_values($fields) as $key => $f)
            {
                $expresion=new Expression("upper({$f})");    
                $builder->where($expresion,'=',$value_param);
            }
        }
        else {
            if($value_param)
            {
                $expresion=new Expression("upper({$fields})");    
                $builder->where($expresion,"=","{$value_param}");
            }
        }       
    }



    /**
     * @param Builder $builder
     * @param array $requestParams
     * @param string $requestField
     * @param string|array $fields
     * @return void
     */
    public static function addSearchInt($builder,$requestParams,$requestField,$fields)
    {
        if(empty($requestField) || empty($fields)) return;

        $value_param=Arr::get($requestParams,$requestField);
        if(empty($value_param)) return;

        if(is_array($fields))
        {
            foreach(array_values($fields) as $key => $f)
            {
                $builder->where($f,'=',$value_param);
            }
        }
        else {
            if(is_array($value_param))
            {
                $builder->whereIn($fields,$value_param);
            }
            else 
            {
                $builder->where($fields,"=",$value_param);
            }
        }       
    }


    /**
     * @param Builder $builder
     * @param array $requestParams
     * @param array $options ['limit'=>20,'order'=>[],'direction'=>'asc|desc']
     * @return stdClass
     */
    public static function createPagination($builder,$requestParams,$options=[])
    {
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

        $tOptions=isset($options["limit"])?$options:$requestParams;

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


        $page=Arr::get($requestParams,'page',1);
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

        $con=$builder->getConnection();

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
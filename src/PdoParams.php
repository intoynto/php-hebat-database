<?php

namespace Intoy\HebatDatabase;

use PDO;
use PDOStatement;

final class PdoParams {

    protected $guid=0;
    protected $keys=[];

    protected function isVal($val){
        if($val===null) return false;

        $test=trim($val);
        return strlen($test)>0;
    }

    protected function mapKey(){
        //return ":param_".(dechex($this->guid++));
        return ":param_".(uniqid());
    }

    protected function mapValue($value){
        $map = [
            'NULL' => PDO::PARAM_NULL,
            'integer' => PDO::PARAM_INT,
            'double' => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'string' => PDO::PARAM_STR,
            'object' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB
        ];
        $type=gettype($value);
        if ($type === 'boolean') {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = null;
        }
        return [$value, $map[$type]];
    }


    function make($fiedlname,$value,$andAtauOr="AND"){
        $str="";
        if($this->isVal($fiedlname) && $this->isVal($value)){
            $key=$this->mapKey();
            $this->keys[$key]=$this->mapValue($value);
            $str=" ".$andAtauOr." ".$fiedlname."=".$key." ";
        }
        return $str;
    }

    function reset(){
        $this->keys=[];
    }


    function getKeys():Array{
        return $this->keys;
    }

    function bindStatement(PDOStatement $s)
    {
        if(count($this->keys)>0)
        {
            foreach($this->keys as $key => $val)
            {
                $s->bindValue($key,$val[0],$val[1]);
            }
        }
    }
}
<?php

declare (strict_types=1);

namespace Intoy\HebatDatabase\Traits;

use Intoy\HebatSupport\Str;

trait GuardAttributes
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [];


    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }


    /**
     * Set the fillable attributes for the model.
     *
     * @param  array  $fillable
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }


    /**
     * Merge new fillable attributes with existing fillable attributes on the model.
     *
     * @param  array  $fillable
     * @return $this
     */
    public function mergeFillable(array $fillable)
    {
        $this->fillable = array_merge($this->fillable, $fillable);

        return $this;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
        if (in_array($key, $this->getFillable())) {
            return true;
        }

        return empty($this->getFillable()) &&
            strpos($key, '.') === false &&
            ! Str::startsWith($key, '_');
    }


    /**
     * Get the fillable attributes of a given array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        //if (count($this->getFillable()) > 0 && !static::$unguarded) 
        if (count($this->getFillable())>0) 
        {
            return array_intersect_key($attributes, array_flip($this->getFillable()));
        }

        return $attributes;
    }
}
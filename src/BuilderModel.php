<?php
declare (strict_types=1);

namespace Intoy\HebatDatabase;

use Intoy\HebatDatabase\Query\Builder as QueryBuilder;

class BuilderModel 
{
    /**
     * The base query builder instance.
     *
     * @var QueryBuilder
     */
    protected $query;


    /**
     * The model being queried.
     *
     * @var Model
     */
    protected $model;



    /**
     * @param QueryBuilder $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query=$query;
    }


    /**
     * Get the model instance being queried.
     *
     * @return Model|static
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }
}
<?php

namespace ScoutElastic\Builders;

use Illuminate\Database\Eloquent\Model;

class SearchBuilder extends FilterBuilder
{
    /**
     * SearchBuilder constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $query
     * @param  callable|null  $callback
     * @param  bool  $softDelete
     * @return void
     */
    public function __construct(Model $model, $query, $callback = null, $softDelete = false)
    {
        parent::__construct($model, $callback, $softDelete);

        $this->query = $query;
    }
}

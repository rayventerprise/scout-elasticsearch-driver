<?php

namespace ScoutElastic;

use ScoutElastic\Builders\FilterBuilder;
use ScoutElastic\Builders\SearchBuilder;

class SearchRule
{
    /**
     * The builder.
     *
     * @var FilterBuilder
     */
    protected $builder;

    /**
     * SearchRule constructor.
     *
     * @param FilterBuilder $builder
     */
    public function __construct(FilterBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Check if this is applicable.
     *
     * @return bool
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * Build the highlight payload.
     *
     * @return array|null
     */
    public function buildHighlightPayload()
    {
    }

    /**
     * Build the query payload.
     *
     * @return array
     */
    public function buildQueryPayload()
    {
        return [
            'must' => [
                'query_string' => [
                    'query' => $this->builder->query,
                ],
            ],
        ];
    }

    /**
     * Build the custom payload which same level with `query`.
     *
     * @return array|null
     */
    public function buildCustomPayload()
    {

    }
}

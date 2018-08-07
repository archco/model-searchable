<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\DB;

/**
 * ModelSearchable
 *
 * @link https://github.com/archco/model-searchable
 * @version v1.0.1
 */
trait ModelSearchable
{
    /**
     * Default options
     *
     * @var array
     */
    protected $searchableDefaultOptions = [
        'columns' => [],
        'mode' => 'like',
        'fulltextMode' => 'natural',
    ];
    protected $fulltextWhere = '';

    /**
     * Options for ModelSearchable (overwrite it)
     *
     * @return array
     */
    public function searchableOptions()
    {
        return [
            'columns' => [],
            'mode' => 'like',
            'fulltextMode' => 'natural',
        ];
    }

    /**
     * Get searchable options as object.
     *
     * @param array $options
     * @return object
     */
    public function getSearchableOptions($options = [])
    {
        $options = array_merge(
            $this->searchableDefaultOptions,
            $this->searchableOptions(),
            $options
        );
        return (object) $options;
    }

    //
    // Query Scopes
    //

    /**
     * search
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  string $searchQuery
     * @param  array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($builder, $searchQuery, $options = [])
    {
        $mode = $this->getSearchableOptions($options)->mode;

        if ($mode == 'fulltext') {
            $builder->searchFulltext($searchQuery, $options);
        } elseif ($mode == 'like') {
            $builder->searchLike($searchQuery, $options);
        }

        return $builder;
    }

    /**
     * searchLike
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $searchQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchLike($builder, $searchQuery, $options = [])
    {
        $searchQuery = $this->querySanitize($searchQuery);
        $queries = explode(' ', $searchQuery);
        $columns = $this->getSearchableOptions($options)->columns;

        foreach ($columns as $i => $col) {
            foreach ($queries as $query) {
                if ($i == 0) {
                    $builder->where($col, 'LIKE', "%{$query}%");
                } else {
                    $builder->orWhere($col, 'LIKE', "%{$query}%");
                }
            }
        }

        return $builder;
    }

    /**
     * searchFulltext
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $searchQuery
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchFulltext($builder, $searchQuery, $options = [])
    {
        $this->fulltextWhere = $this->makeFulltextWhere($searchQuery, $options);

        return $builder->whereRaw($this->fulltextWhere);
    }

    /**
     * selectWithScore (for fulltext mode)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSelectWithScore($builder, $columns = ['*'])
    {
        $opt = $this->getSearchableOptions();

        if ($opt->mode != 'fulltext' || $this->fulltextWhere == '') {
            return $builder;
        } else {
            $columns = array_merge($columns, [DB::raw("{$this->fulltextWhere} AS score")]);
            return $builder->select($columns);
        }
    }

    /**
     * getWithScore (for fulltext mode)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeGetWithScore($builder, $columns = ['*'])
    {
        $opt = $this->getSearchableOptions();

        if ($opt->mode != 'fulltext' || $this->fulltextWhere == '') {
            return $builder->get($columns);
        } else {
            $columns = array_merge($columns, [DB::raw("{$this->fulltextWhere} AS score")]);
            return $builder->get($columns);
        }
    }

    protected function querySanitize($query)
    {
        return trim($query);
    }

    protected function getFulltextMode($modeName)
    {
        $modes = [
            'boolean' => 'IN BOOLEAN MODE',
            'natural' => 'IN NATURAL LANGUAGE MODE',
            'expansion' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION'
        ];
        return $modes[$modeName];
    }

    protected function makeFulltextWhere($searchQuery, $options = [])
    {
        $searchQuery = $this->querySanitize($searchQuery);
        $opt = $this->getSearchableOptions($options);
        $columns = implode(',', $opt->columns);
        $fulltextMode = $this->getFulltextMode($opt->fulltextMode);

        return "MATCH ({$columns}) AGAINST ('{$searchQuery}' {$fulltextMode})";
    }
}

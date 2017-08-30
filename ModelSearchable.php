<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\DB;

/**
 * ModelSearchable
 *
 * @updated 2017-08-30
 * @link https://github.com/archco/model-searchable
 */
trait ModelSearchable
{
    /**
     * Options
     * protected $searchableColumns; // array, search target columns.
     * protected $searchMode; // string, 'like' or 'fulltext'
     * protected $fulltextMode; // string, 'boolean' or 'natural' or 'expansion'
     */

    protected $fulltextWhere = '';


    /************************************************************
      Scopes
    *************************************************************/

    /**
     * search
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  string $searchQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($builder, $searchQuery)
    {
        $mode = $this->getSearchMode();

        if ($mode == 'fulltext') {
            $builder->searchFulltext($searchQuery);
        } elseif ($mode == 'like') {
            $builder->searchLike($searchQuery);
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
    public function scopeSearchLike($builder, $searchQuery)
    {
        $searchQuery = $this->querySanitize($searchQuery);
        $queries = explode(' ', $searchQuery);
        $columns = $this->getSearchableColumns();

        foreach ($columns as $col) {
            foreach ($queries as $query) {
                $builder->orWhere($col, 'LIKE', "%{$query}%");
            }
        }

        return $builder;
    }

    /**
     * searchFulltext
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $searchQuery
     * @param  string  $modeName  'boolean'|'natural'|'expansion'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchFulltext($builder, $searchQuery, $modeName = null)
    {
        $searchQuery = $this->querySanitize($searchQuery);
        $columns = implode(',', $this->getSearchableColumns());
        $mode = $this->getFulltextMode($modeName);
        $this->fulltextWhere = "MATCH ({$columns}) AGAINST ('{$searchQuery}' {$mode})";

        return $builder
            ->whereRaw($this->fulltextWhere);
    }

    /**
     * selectWithScore
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSelectWithScore($builder, $columns = ['*'])
    {
        if ($this->getSearchMode() != 'fulltext' || $this->fulltextWhere == '') {
            return $builder;
        } else {
            $columns = array_merge($columns, [DB::raw("{$this->fulltextWhere} AS score")]);
            return $builder->select($columns);
        }
    }

    /**
     * getWithScore
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeGetWithScore($builder, $columns = ['*'])
    {
        if ($this->getSearchMode() != 'fulltext' || $this->fulltextWhere == '') {
            return $builder->get($columns);
        } else {
            $columns = array_merge($columns, [DB::raw("{$this->fulltextWhere} AS score")]);
            return $builder->get($columns);
        }
    }

    protected function getSearchableColumns()
    {
        return $this->searchableColumns ?? [];
    }

    protected function getSearchMode()
    {
        return $this->searchMode ?? 'like';
    }

    protected function getFulltextMode($modeName = null)
    {
        $modeName = $modeName ?? $this->fulltextMode ?? 'boolean';
        $modes = [
            'boolean' => 'IN BOOLEAN MODE',
            'natural' => 'IN NATURAL LANGUAGE MODE',
            'expansion' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION'
        ];

        return $modes[$modeName];
    }

    protected function querySanitize($query)
    {
        return trim($query);
    }
}

<?php

namespace App\Support\Traits;

/**
 * ModelSearchable
 *
 * @date 2017-07-06
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

        return $builder
            ->whereRaw("MATCH ({$columns}) AGAINST (? {$mode})", [$searchQuery]);
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

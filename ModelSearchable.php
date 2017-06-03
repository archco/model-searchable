<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * The laravel eloquent trait for mysql search.
 *
 * @link https://github.com/archco/model-searchable
 * @license MIT
 */
trait ModelSearchable
{
    /**
     * Options
     * protected $searchableColumns; // array, search target columns.
     * protected $searchMode; // string, 'like' or 'fulltext'
     * protected $fulltextMode; // string, 'boolean' or 'natural' or 'expension'
     */

    /**
     * $searchBuilder
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $searchBuilder;

    /**
     * static search
     *
     * @param  string $query
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search($query, $builder = null)
    {
        return (new static)->searchBindBuilder($query, $builder);
    }

    /**
     * searchBindBuilder
     *
     * @param  string $query
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchBindBuilder($query, $builder = null)
    {
        $this->setSearchBuilder($builder);
        $mode = $this->getSearchMode();

        if ($mode == 'fulltext') {
            return $this->searchFulltext($query);
        } elseif ($mode == 'like') {
            return $this->searchLike($query);
        }

        return null;
    }

    /**
     * search 'like' mode
     *
     * @param  string $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchLike($query)
    {
        $query = $this->querySanitize($query);
        $columns = $this->getSearchableColumns();
        $builder = $this->getSearchBuilder();

        foreach ($columns as $col) {
            $builder->orWhere($col, 'LIKE', "%{$query}%");
        }

        return $builder;
    }

    /**
     * search 'fulltext' mode
     *
     * @param  string $query
     * @param  string $modeName 'boolean' or 'natural' or 'expension'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchFulltext($query, $modeName = null)
    {
        $query = $this->querySanitize($query);
        $builder = $this->getSearchBuilder();
        $columns = implode(',', $this->getSearchableColumns());
        $mode = $this->getFulltextMode($modeName);

        return $builder->whereRaw("MATCH ({$columns}) AGAINST (? {$mode})", [$query]);
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
            'expension' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPENSION'
        ];

        return $modes[$modeName];
    }

    protected function querySanitize($query)
    {
        return trim($query);
    }

    protected function getSearchBuilder()
    {
        return $this->searchBuilder ?? static::query();
    }

    protected function setSearchBuilder($builder)
    {
        $this->searchBuilder = $builder;
    }
}

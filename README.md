# model-searchable

The laravel eloquent trait for mysql search.

## Configuration

``` php
// in Model..
namespace App;

use App\Support\Traits\ModelSearchable;

class Post extends Model
{
    use ModelSearchable;

    /**
     * @Overwrite Options for ModelSearchable
     *
     * @return array
     */
    public function searchableOptions()
    {
        return [
            'columns' => ['title', 'content'],
            'mode' => 'like', // like | fulltext
            'fulltextMode' => 'natural', // natural | boolean | expansion
        ];
    }
}
```

## Usage

### Search

``` php
// Simple search.
Post::search('php')->get();

// Searching with where condition.
Post::whereYear('created_at', '2017')->search('php')->get();

// Search with specifying options.
Post::search('eloquent', ['mode' => 'like', 'columns' => ['title']])->get();
```

### Fulltext search

``` php
// Retrieving models with score when fulltext mode.
Post::search('laravel eloquent')->getWithScore();

// Select specific columns and score.
Post::search('laravel eloquent')->getWithScore(['id', 'title']);
```

## License

[MIT License](https://github.com/archco/model-searchable/blob/master/LICENSE)

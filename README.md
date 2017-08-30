# model-searchable
The laravel eloquent trait for mysql search.

## Configuration
```php
// in Model..
namespace App;

use App\Support\Traits\ModelSearchable;

class Post extends Model
{
    use ModelSearchable;

    /** ModelSearchable's options */
    protected $searchableColumns = ['title', 'content'];
    protected $searchMode = 'fulltext'; // 'like' or 'fulltext'.
    protected $fulltextMode = 'boolean'; // 'boolean' or 'natural' or 'expansion'.
}
```

## Usage
### Search
```php
// Simple search.
Post::search('php')->get();
// Searching with where condition.
Post::whereYear('created_at', '2017')->search('php')->get();
```

### Fulltext search
```php
// Retrieving models with score when fulltext mode.
Post::search('laravel eloquent')->getWithScore();
// Select specific columns and score.
Post::search('laravel eloquent')->getWithScore(['id', 'title']);
```

## License
[MIT License](https://github.com/archco/model-searchable/blob/master/LICENSE)

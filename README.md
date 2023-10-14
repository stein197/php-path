# PHP path class
This tiny class provides means to manage path strings easily.

## Installation
```
composer require stein197/path
```

## Usage
```php
use Stein197\Path;
Path::join(['/var/www/html', 'assets', 'index.js'], [
	'separator' => '/'
]); // '/var/www/html/assets/index.js'
Path::normalize('/a/b/..////d/./c', ['separator' => '\\', 'trailingSlash' => true]); // '\\a\\d\\c\\'
$p = new Path('/a/b/c');
$p->isAbsolute(); // true
$p->toRelative('/a'); // 'b/c'
```

> For further information refer to the docblocks in the source code.

## Composer scripts
- `test` Run unit tests

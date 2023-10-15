# PHP path class
Provides a tiny class `Stein197\FileSystem\Path` that provides means to manage path strings easily.

## Installation
```
composer require stein197/path
```

## Usage
The following code gives only a glipse of the available API provided by the package. The more detailed documentation can be found in the source code.
```php
require __DIR__ . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Stein197\FileSystem\Path;

// Instance methods
$p = new Path('/var/www/html'); // Create a path object
echo $p; // Call overriden __toString() method that automatically normalizes the path
echo $p->path; // '/var/www/html'. Return the path passed down to the constructor
echo $p->equals('/var/www\\html'); // true. Automatically normalize both paths and compare them
echo $p->isAbsolute(); // true
echo $p->isRelative(); // false
echo $p->isRoot(); // false
echo $p->getParent(); // Path('/var/www'). Return normalized parent path
echo $p->getType(); // PathType::Unix
echo $p->toAbsolute('/usr/bin'); // Path('/var/www/html'). Return the path itself since it's already absolute
echo $p->toRelative('/var'); // Path('www/html')
echo $p->format(['separator' => '/', 'trailingSlash' => true]); // '/var/www/html/'

// Static methods
echo Path::join('var', 'www/html'); // Path('var/www/html'). Concatenate passed strings
echo Path::expand('$HOME/downloads'); // Path('/home/admin/downloads'). Expand environment variables
echo Path::normalize('var/\\www\\./../www/html'); // Path('var/www/html'). Normalize given path
```

> For further information refer to the docblocks in the source code.

## Composer scripts
- `test` Run unit tests

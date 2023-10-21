# PHP path class
Provides a tiny class `Stein197\FileSystem\Path` that provides means to manage path strings easily.

## Installation
```
composer require stein197/path
```

## Usage
The following code gives only a glipse of the available API provided by the package. The more detailed documentation can be found in the source code.
```php
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Stein197\FileSystem\Path;

$p = Path::new('/var/www/html/project/public');

// Stringable interface implementation
(string) $p; // '/var/www/html/project/public'

// ArrayAccess interface implementation
isset($p[1]);   // true, it's 'var' part
$p[-2];         // 'project'
$p[3] = 'html'; // an exception, paths are read-only
unset($p[4]);   // an exception, paths are read-only

// Countable interface implementation
sizeof($p); // 5

// Iterator interface implementation
[...$p]; // ['', 'var', 'www', 'html', 'project', 'public']

// Stein197\Equalable interface implementation
$p->equals('\\var///www/html/project/public/'); // true

// Public readonly properties
$p->isDOS;      // false
$p->isUnix;     // true
$p->isRoot;     // false
$p->isAbsolute; // true
$p->isRelative; // false
$p->depth;      // 5
$p->drive;      // null; Valid only for DOS paths
$p->path;       // '/var/www/html/project/public'

// Dynamic methods
$p->getElement(1);                                        // 'var'; The same as $p[1]
$p->getParent();                                          // Path('/var/www/html/project')
$p->getSubpath(2, -2);                                    // Path('www/html/project')
$p->toAbsolute('/');                                      // Path('/var/www/html/project/public'); The path itself since it's already absolute
$p->toRelative('/var/www');                               // Path('html/project/public')
$p->format([
	'separator' => '\\',
	'trailingSlash' => true
]);                                                       // '\\var\\www\\html\\project\\public\\'
$p->startsWith('/var');                                   // true
$p->endsWith('public');                                   // true
$p->isChildOf('/var/www/html/project');                   // true
$p->isParentOf('/var/www/html/project/public/index.php'); // true
$p->includes('www/html');                                 // true
$p->firstIndexOf('www');                                  // 2
$p->lastIndexOf('public');                                // 5

// Static methods
Path::join('vendor', 'bin/phpunit');               // Path('vendor/bin/phpunit')
Path::expand('%SystemRoot%/Fonts');                // Path('C:\\Windows\\Fonts')
Path::normalize('/var////././www\\..\\www/html/'); // Path('/var/www/html')
Path::new('.');                                    // Path('.')
Path::findCommonBase(
	'/var/www/html',
	'/var/usr/bin'
);                                                 // Path('/var')
```

> For further information refer to the docblocks in the source code.

## Composer scripts
- `test` Run unit tests

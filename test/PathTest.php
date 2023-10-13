<?php
namespace Stein197\FileSystem;

use InvalidArgumentException;
use function describe;
use function expect;
use function test;
use const DIRECTORY_SEPARATOR;

describe('Path::__construct()', function () {
	test('Should throw an exception when the string is empty', function () {
		expect(fn () => new Path(''))->toThrow(InvalidArgumentException::class, 'Cannot instantiate a path object: the path string is empty');
	});
	test('Should instantiate an object when the string is not empty', function () {
		$p = new Path('public/index.html');
		expect($p->path)->toBe('public/index.html');
	});
});

describe('Path::__toString()', function () {
	test('Should return normalized path', function () {
		expect((string) new Path('c:\\Windows///Users/./Admin/..\\\\Admin/'))->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Admin');
		expect((string) new Path('\\var///www/./html/..\\\\public/'))->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'public');
	});
	test('Should return the initial path if the normalization cannot be performed', function () {
		expect((string) new Path('..'))->toBe('..');
		expect((string) new Path('var/..\\..'))->toBe('var/..\\..');
	});
});
describe('Path::equals()', function () {})->skip();

describe('Path::isAbsolute()', function () {
	test('Should return true for normalized root paths', function () {
		expect((new Path('C:'))->isAbsolute())->toBeTrue();
		expect((new Path('C:/'))->isAbsolute())->toBeTrue();
		expect((new Path('C:\\'))->isAbsolute())->toBeTrue();
		expect((new Path('/'))->isAbsolute())->toBeTrue();
		expect((new Path('\\'))->isAbsolute())->toBeTrue();
	});
	test('Should return true for denormalized root paths', function () {
		expect((new Path('C://'))->isAbsolute())->toBeTrue();
		expect((new Path('C:\\/'))->isAbsolute())->toBeTrue();
		expect((new Path('/\\'))->isAbsolute())->toBeTrue();
		expect((new Path('\\/'))->isAbsolute())->toBeTrue();
	});
	test('Should return true for normalized absolute paths', function () {
		expect((new Path('c:\\Windows\\Users'))->isAbsolute())->toBeTrue();
		expect((new Path('/usr/www/root'))->isAbsolute())->toBeTrue();
	});
	test('Should return true for denormalized absolute paths', function () {
		expect((new Path('c:\\\\Windows\\Users\\'))->isAbsolute())->toBeTrue();
		expect((new Path('/usr////www/root///'))->isAbsolute())->toBeTrue();
	});
	test('Should return false for normalized relative paths', function () {
		expect((new Path('vendor/autoload.php'))->isAbsolute())->toBeFalse();
		expect((new Path('users\\Admin'))->isAbsolute())->toBeFalse();
	});
	test('Should return false for denormalized relative paths', function () {
		expect((new Path('vendor///autoload.php'))->isAbsolute())->toBeFalse();
		expect((new Path('users\\\\Admin\\'))->isAbsolute())->toBeFalse();
	});
	test('Should return false for current directory', function () {
		expect((new Path('.'))->isAbsolute())->toBeFalse();
	});
	test('Should return false for parent directory', function () {
		expect((new Path('..'))->isAbsolute())->toBeFalse();
	});
	test('Should return for normalized paths that start with a current directory', function () {
		expect((new Path('./vendor/autoload.php'))->isAbsolute())->toBeFalse();
		expect((new Path('.\users\\Admin'))->isAbsolute())->toBeFalse();
	});
	test('Should return for normalized paths that start with a parent directory', function () {
		expect((new Path('..\\vendor///autoload.php'))->isAbsolute())->toBeFalse();
		expect((new Path('../users\\\\Admin\\'))->isAbsolute())->toBeFalse();
	});
});

describe('Path::isRelative()', function () {
	test('Should return false for root paths', function () {
		expect((new Path('C:'))->isRelative())->toBeFalse();
		expect((new Path('c:/'))->isRelative())->toBeFalse();
		expect((new Path('c:\\'))->isRelative())->toBeFalse();
		expect((new Path('/'))->isRelative())->toBeFalse();
		expect((new Path('\\'))->isRelative())->toBeFalse();
	});
	test('Should return false for absolute paths', function () {
		expect((new Path('C:\\Windows\\'))->isRelative())->toBeFalse();
		expect((new Path('c:/users/'))->isRelative())->toBeFalse();
		expect((new Path('/usr/bin'))->isRelative())->toBeFalse();
	});
	test('Should return true for relative paths', function () {
		expect((new Path('file.txt'))->isRelative())->toBeTrue();
		expect((new Path('vendor/autoload.php'))->isRelative())->toBeTrue();
		expect((new Path('vendor/phpunit\\\\phpunit/'))->isRelative())->toBeTrue();
	});
	test('Should return true for current directory', function () {
		expect((new Path('.'))->isRelative())->toBeTrue();
	});
	test('Should return true for parent directory', function () {
		expect((new Path('..'))->isRelative())->toBeTrue();
	});
	test('Should return true for paths that start with a current directory', function () {
		expect((new Path('./vendor'))->isRelative())->toBeTrue();
	});
	test('Should return true for paths that start with a parent directory', function () {
		expect((new Path('..\\users'))->isRelative())->toBeTrue();
	});
});

describe('Path::isRoot()', function () {
	test('Windows: normalized path', function () {
		expect((new Path('C:'))->isRoot())->toBeTrue();
		expect((new Path('c:'))->isRoot())->toBeTrue();
		expect((new Path('C:\\'))->isRoot())->toBeTrue();
		expect((new Path('C:/'))->isRoot())->toBeTrue();
	});
	test('Windows: denormalized path', function () {
		expect((new Path('C:\\//'))->isRoot())->toBeTrue();
		expect((new Path('C:/\\'))->isRoot())->toBeTrue();
	});
	test('Unix: normalized path', function () {
		expect((new Path('/'))->isRoot())->toBeTrue();
		expect((new Path('\\'))->isRoot())->toBeTrue();
	});
	test('Unix: denormalized path', function () {
		expect((new Path('\\/'))->isRoot())->toBeTrue();
		expect((new Path('/\\'))->isRoot())->toBeTrue();
	});
	test('none', function () {
		expect((new Path('C:\\Windows'))->isRoot())->toBeFalse();
		expect((new Path('c:/Windows'))->isRoot())->toBeFalse();
		expect((new Path('/usr/bin'))->isRoot())->toBeFalse();
		expect((new Path('\\usr/bin'))->isRoot())->toBeFalse();
		expect((new Path('file.txt'))->isRoot())->toBeFalse();
	});
});

describe('Path::getParent()', function () {})->skip();

describe('Path::getType()', function () {
	test('Windows: normalized path', function () {
		expect((new Path('C:'))->getType())->toBe(PathType::Windows);
		expect((new Path('c:'))->getType())->toBe(PathType::Windows);
		expect((new Path('c:\\'))->getType())->toBe(PathType::Windows);
		expect((new Path('C:/'))->getType())->toBe(PathType::Windows);
	});
	test('Windows: denormalized path', function () {
		expect((new Path('c:/\\'))->getType())->toBe(PathType::Windows);
		expect((new Path('C:/\\'))->getType())->toBe(PathType::Windows);
	});
	test('Unix: normalized path', function () {
		expect((new Path('/'))->getType())->toBe(PathType::Unix);
		expect((new Path('\\'))->getType())->toBe(PathType::Unix);
		expect((new Path('/usr'))->getType())->toBe(PathType::Unix);
		expect((new Path('\\usr/bin'))->getType())->toBe(PathType::Unix);
	});
	test('Unix: denormalized path', function () {
		expect((new Path('/\\'))->getType())->toBe(PathType::Unix);
		expect((new Path('/\\'))->getType())->toBe(PathType::Unix);
		expect((new Path('/usr//'))->getType())->toBe(PathType::Unix);
		expect((new Path('\\usr\\/bin'))->getType())->toBe(PathType::Unix);
	});
	test('null: normalized path', function () {
		expect((new Path('filename.txt'))->getType())->toBeNull();
		expect((new Path('.git/hooks'))->getType())->toBeNull();
	});
	test('null: denormalized path', function () {
		expect((new Path('filename.txt\\/'))->getType())->toBeNull();
		expect((new Path('.git//\\hooks'))->getType())->toBeNull();
	});
});

describe('Path::toAbsolute()', function () {
	test('Should throw an exception when the base path is relative', function () {
		expect(fn () => (new Path('.'))->toAbsolute('usr/bin'))->toThrow(InvalidArgumentException::class, "Cannot convert the path '.' to absolute: the base 'usr/bin' is not absolute");
	});
	test('Should throw an exception when there are too many parent jumps in the current directory', function () {
		expect(fn () => (new Path('vendor/../../..'))->toAbsolute('C:\\Windows\\'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'C:\\Windows\\' . DIRECTORY_SEPARATOR . 'vendor/../../..\': too many parent jumps');
	});
	test('Should return the path itself when it is already absolute', function () {
		expect((new Path('/usr/bin'))->toAbsolute('C:\\Windows')->path)->toBe('/usr/bin');
		expect((new Path('C:\\Windows'))->toAbsolute('/usr/bin')->path)->toBe('C:\\Windows');
	});
	test('Should return correct result when the base is root', function () {
		expect((new Path('vendor/autoload.php'))->toAbsolute('C:')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect((new Path('vendor/autoload.php'))->toAbsolute('C:\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect((new Path('vendor/autoload.php'))->toAbsolute('/')->path)->toBe(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
	});
	test('Should return the base path when the current one is a current directory', function () {
		expect((new Path('.'))->toAbsolute('/')->path)->toBe(DIRECTORY_SEPARATOR);
		expect((new Path('.'))->toAbsolute('c:')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((new Path('.'))->toAbsolute('C:\\Windows')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect((new Path('.'))->toAbsolute('/usr//\\bin/.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should return a parent of the base when the current one is a parent directory', function () {
		expect((new Path('..'))->toAbsolute('C:\\Windows')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((new Path('..'))->toAbsolute('/usr//\\bin/.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr');
		expect((new Path('..'))->toAbsolute('C:\\Windows\\Users')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect((new Path('..'))->toAbsolute('/usr//\\bin/php')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should correctly jump out when the current path contains enough parent jumps', function () {
		expect((new Path('vendor/../../..'))->toAbsolute('C:\\Windows\\Users\\Downloads')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
	});
	test('Should return an absolute path', function () {
		expect((new Path('vendor/autoload.php'))->toAbsolute('C:\\inetpub\\wwwroot\\project')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'inetpub' . DIRECTORY_SEPARATOR . 'wwwroot' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
	});
});

describe('Path::toRelative()', function () {
	test('Should throw an exception when the base path is relative', function () {
		expect(fn () => (new Path('/usr/bin'))->toRelative('home'))->toThrow(InvalidArgumentException::class, "Cannot convert the path '/usr/bin' to relative: the base 'home' is not absolute");
		expect(fn () => (new Path('C:\\Windows\\'))->toRelative('home'))->toThrow(InvalidArgumentException::class, "Cannot convert the path 'C:\\Windows\\' to relative: the base 'home' is not absolute");
	});
	test('Should throw an exception when the base path is not a parent of the current path', function () {
		expect(fn () => (new Path('/usr/bin'))->toRelative('/home'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'/usr/bin\' to relative: the base \'/home\' is not a parent of the path');
		expect(fn () => (new Path('C:\\Windows\\Users'))->toRelative('D:\\Games'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'C:\\Windows\\Users\' to relative: the base \'D:\\Games\' is not a parent of the path');
	});
	test('Should return correct result when the base is an absolute path', function () {
		expect((new Path('C:\\Windows\\Users/Admin\\Downloads/file.txt'))->toRelative('c:/Windows/Users/Admin')->path)->toBe('Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect((new Path('/var/www/html\\project/public\\index.php'))->toRelative('\\var/www/html/project')->path)->toBe('public' . DIRECTORY_SEPARATOR . 'index.php');
	});
	test('Should return correct result when the base is a root path', function () {
		expect((new Path('C:\\Windows\\Users/Admin\\Downloads/file.txt'))->toRelative('C:')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect((new Path('/var/www/html\\project/public\\index.php'))->toRelative('\\')->path)->toBe('var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php');
	});
	test('Should return the path itself when the path is already relative', function () {
		expect((new Path('Downloads\\file.txt'))->toRelative('C:')->path)->toBe('Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect((new Path('public/index.php'))->toRelative('/var/www/html/project')->path)->toBe('public' . DIRECTORY_SEPARATOR . 'index.php');
	});
});

describe('Path::format()', function () {})->skip();
describe('Path::resolve()', function () {})->skip();
describe('Path::expand()', function () {})->skip();

describe('Path::normalize()', function () {
	test('Should return a current directory when the string is empty', function () {
		expect(Path::normalize('')->path)->toBe('.');
	});
	test('Should throw an exception when the path is a parent directory', function () {
		expect(fn () => Path::normalize('..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'..\': too many parent jumps');
		expect(fn () => Path::normalize('../..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'../..\': too many parent jumps');
		expect(fn () => Path::normalize('../'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'../\': too many parent jumps');
		expect(fn () => Path::normalize('../../'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'../../\': too many parent jumps');
	});
	test('Should throw an exception when it is a jump out of root', function () {
		expect(fn () => Path::normalize('c:\\..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'c:\\..\': too many parent jumps');
		expect(fn () => Path::normalize('c:\\../.'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'c:\\../.\': too many parent jumps');
		expect(fn () => Path::normalize('/..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'/..\': too many parent jumps');
		expect(fn () => Path::normalize('/..\\..\\'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'/..\\..\\\': too many parent jumps');
	});
	test('Should throw an exception when there are too many parent jumps', function () {
		expect(fn () => Path::normalize('vendor/bin/../../..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'vendor/bin/../../..\': too many parent jumps');
	});
	test('Should return \'.\' when the path is a current directory', function () {
		expect(Path::normalize('.')->path)->toBe('.');
		expect(Path::normalize('./')->path)->toBe('.');
		expect(Path::normalize('.\\')->path)->toBe('.');
	});
	test('Should return \'.\' when the resulting path is a current directory', function () {
		expect(Path::normalize('vendor/..\\bin/..\\')->path)->toBe('.');
		expect(Path::normalize('vendor/bin/..\\..')->path)->toBe('.');
	});
	test('Should return the string itself when it is a single name', function () {
		expect(Path::normalize('file.txt')->path)->toBe('file.txt');
		expect(Path::normalize('vendor')->path)->toBe('vendor');
		expect(Path::normalize('.git')->path)->toBe('.git');
	});
	test('Should collapse redundant directory separators', function () {
		expect(Path::normalize('vendor///autoload.php')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect(Path::normalize('vendor///autoload.php')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect(Path::normalize('Windows\\\\Fonts')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('Windows\\\\Fonts')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
	});
	test('Should unify directory separators to DIRECTORY_SEPARATOR constant', function () {
		expect(Path::normalize('a/b\\c')->path)->toBe('a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c');
	});
	test('Should trim trailing slash', function () {
		expect(Path::normalize('vendor/bin/')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::normalize('vendor\\bin\\')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should remove current directory parts', function () {
		expect(Path::normalize('./usr/www/./././html/.')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html');
	});
	test('Should collapse parent directory parts', function () {
		expect(Path::normalize('usr/..')->path)->toBe('.');
		expect(Path::normalize('usr/../home')->path)->toBe('home');
		expect(Path::normalize('usr/../home/..')->path)->toBe('.');
		expect(Path::normalize('usr/../home/user/../admin')->path)->toBe('home' . DIRECTORY_SEPARATOR . 'admin');
	});
	test('Should capitalize drive letters', function () {
		expect(Path::normalize('c:\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('c:/')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
	});
	test('Should correctly normalize root paths', function () {
		expect(Path::normalize('c:')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('c:\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('C://')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('/')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::normalize('\\')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::normalize('////')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::normalize('\\\\')->path)->toBe(DIRECTORY_SEPARATOR);
	});
	test('Should correctly normalize absolute paths', function () {
		expect(Path::normalize('c:\\Windows/Fonts')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('c:\\./Windows/..\\\\/Windows/./Fonts\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('\\usr/bin\\\\php')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect(Path::normalize('/./usr/../usr/bin\\\\php\\.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	test('Should correctly normalize relative paths', function () {
		expect(Path::normalize('Windows/Fonts')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('./Windows/..\\\\/Windows/./Fonts\\')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('usr/bin\\\\php')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect(Path::normalize('./usr/../usr/bin\\\\php\\.')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	test('Should return the string itself when it\'s already normalized', function () {
		expect(Path::normalize('Windows' . DIRECTORY_SEPARATOR . 'Fonts')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::normalize('usr' . DIRECTORY_SEPARATOR . 'bin')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should return the same result when normalizing twice', function () {
		$p1 = Path::normalize('C://\\.//.\\Windows/..\\Windows/Fonts/../Fonts');
		$p2 = Path::normalize($p1);
		expect($p2->path)->toBe($p1->path);
	});
	test('Complex examples', function () {})->skip();
});

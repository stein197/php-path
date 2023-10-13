<?php
namespace Stein197;

use InvalidArgumentException;
use function describe;
use function expect;
use function test;
use const DIRECTORY_SEPARATOR;

describe('\\Stein197\\Path::__construct()', function () {
	test('Should throw an exception when the string is empty', function () {
		expect(fn () => new Path(''))->toThrow(InvalidArgumentException::class, 'Cannot instantiate a path object: the path string is empty');
	});
	test('Should instantiate an object when the string is not empty', function () {
		$p = new Path('public/index.html');
		expect($p->raw)->toBe('public/index.html');
	});
});

describe('\\Stein197\\Path::__toString()', function () {})->skip();
describe('\\Stein197\\Path::equals()', function () {})->skip();

describe('\\Stein197\\Path::isAbsolute()', function () {
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

describe('\\Stein197\\Path::isRelative()', function () {
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

describe('\\Stein197\\Path::isRoot()', function () {
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

describe('\\Stein197\\Path::getParent()', function () {})->skip();

describe('\\Stein197\\Path::getType()', function () {
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

describe('\\Stein197\\Path::toAbsolute()', function () {})->skip();
describe('\\Stein197\\Path::toRelative()', function () {})->skip();
describe('\\Stein197\\Path::format()', function () {})->skip();
describe('\\Stein197\\Path::resolve()', function () {})->skip();
describe('\\Stein197\\Path::expand()', function () {})->skip();
describe('\\Stein197\\Path::normalize()', function () {
	test('Should throw an exception when the path is empty', function () {
		expect(fn () => Path::normalize(''))->toThrow(InvalidArgumentException::class, 'Cannot instantiate a path object: the path string is empty');
	});
	test('Should throw an exception when the path is a parent directory', function () {
		expect(fn () => Path::normalize('..'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'..\': too many parent jumps');
		expect(fn () => Path::normalize('../..'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'../..\': too many parent jumps');
		expect(fn () => Path::normalize('../'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'../\': too many parent jumps');
		expect(fn () => Path::normalize('../../'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'../../\': too many parent jumps');
	});
	test('Should throw an exception when it is a jump out of root', function () {
		expect(fn () => Path::normalize('c:\\..'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'c:\\..\': too many parent jumps');
		expect(fn () => Path::normalize('c:\\../.'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'c:\\../.\': too many parent jumps');
		expect(fn () => Path::normalize('/..'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'/..\': too many parent jumps');
		expect(fn () => Path::normalize('/..\\..\\'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'/../\\..\\\': too many parent jumps');
	});
	test('Should throw an exception when there are too many parent jumps', function () {
		expect(fn () => Path::normalize('vendor/bin/../../..'))->toThrow(InvalidArgumentException::class, 'Cannot normalise the path \'vendor/bin/../../..\': too many parent jumps');
	});
	test('Should return \'.\' when the path is a current directory', function () {
		expect((string) Path::normalize('.'))->toBe('.');
		expect((string) Path::normalize('./'))->toBe('.');
		expect((string) Path::normalize('.\\'))->toBe('.');
	});
	test('Should return \'.\' when the resulting path is a current directory', function () {
		expect((string) Path::normalize('vendor/..\\bin/..\\'))->toBe('.');
		expect((string) Path::normalize('vendor/bin/..\\..'))->toBe('.');
	});
	test('Should return the string itself when it is a single name', function () {
		expect((string) Path::normalize('file.txt'))->toBe('file.txt');
		expect((string) Path::normalize('vendor'))->toBe('vendor');
		expect((string) Path::normalize('.git'))->toBe('.git');
	});
	test('Should collapse redundant directory separators', function () {
		expect((string) Path::normalize('vendor///autoload.php'))->toBe('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect((string) Path::normalize('vendor///autoload.php'))->toBe('vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect((string) Path::normalize('Windows\\\\Fonts'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('Windows\\\\Fonts'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
	});
	test('Should unify directory separators to DIRECTORY_SEPARATOR constant', function () {
		expect((string) Path::normalize('a/b\\c'))->toBe('a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c');
	});
	test('Should trim trailing slash', function () {
		expect((string) Path::normalize('vendor/bin/'))->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect((string) Path::normalize('vendor\\bin\\'))->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should remove current directory parts', function () {
		expect((string) Path::normalize('./usr/www/./././html/.'))->toBe('usr' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html');
	});
	test('Should collapse parent directory parts', function () {
		expect((string) Path::normalize('usr/..'))->toBe('.');
		expect((string) Path::normalize('usr/../home'))->toBe('home');
		expect((string) Path::normalize('usr/../home/..'))->toBe('.');
		expect((string) Path::normalize('usr/../home/user/../admin'))->toBe('home' . DIRECTORY_SEPARATOR . 'admin');
	});
	test('Should capitalize drive letters', function () {
		expect((string) Path::normalize('c:\\'))->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('c:/'))->toBe('C:' . DIRECTORY_SEPARATOR);
	});
	test('Should correctly normalize root paths', function () {
		expect((string) Path::normalize('c:'))->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('c:\\'))->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('C://'))->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('/'))->toBe(DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('\\'))->toBe(DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('////'))->toBe(DIRECTORY_SEPARATOR);
		expect((string) Path::normalize('\\\\'))->toBe(DIRECTORY_SEPARATOR);
	});
	test('Should correctly normalize absolute paths', function () {
		expect((string) Path::normalize('c:\\Windows/Fonts'))->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('c:\\./Windows/..\\\\/Windows/./Fonts\\'))->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('\\usr/bin\\\\php'))->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect((string) Path::normalize('/./usr/../usr/bin\\\\php\\.'))->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	test('Should correctly normalize relative paths', function () {
		expect((string) Path::normalize('Windows/Fonts'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('./Windows/..\\\\/Windows/./Fonts\\'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('usr/bin\\\\php'))->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect((string) Path::normalize('./usr/../usr/bin\\\\php\\.'))->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	test('Should return the string itself when it\'s already normalized', function () {
		expect((string) Path::normalize('Windows' . DIRECTORY_SEPARATOR . 'Fonts'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts'))->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect((string) Path::normalize(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin'))->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
		expect((string) Path::normalize('usr' . DIRECTORY_SEPARATOR . 'bin'))->toBe('usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Complex examples', function () {})->skip();
});

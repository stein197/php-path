<?php
namespace Stein197;

use InvalidArgumentException;
use function describe;
use function expect;
use function test;

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
describe('\\Stein197\\Path::normalize()', function () {})->skip();

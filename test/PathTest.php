<?php
/*
The entire API should be tested against these cases:
- Root path (DOS-like and Unix-like, normalized and denormalized)
- Absolute path (DOS-like and Unix-like, normalized and denormalized)
- Relative path (DOS-like and Unix-like, normalized and denormalized)
- Relative path with only a single element
- Current directory
- Parent directory
- Path starting with a current directory
- Path starting with a parent directory
- Empty string
*/
namespace Stein197\FileSystem;

use Directory;
use InvalidArgumentException;
use stdClass;
use function describe;
use function expect;
use function getenv;
use function putenv;
use function test;
use const DIRECTORY_SEPARATOR;

beforeAll(fn () => putenv('GLOBAL_VARIABLE=' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html'));
afterAll(fn () => putenv('GLOBAL_VARIABLE'));

describe('Path->isDOS', function () {
	test('Should be true when it is a DOS-like path and it is normalized', function () {
		expect(Path::new('C:')->isDOS)->toBeTrue();
		expect(Path::new('c:')->isDOS)->toBeTrue();
		expect(Path::new('c:\\Windows')->isDOS)->toBeTrue();
		expect(Path::new('C:/Windows')->isDOS)->toBeTrue();
	});
	test('Should be true when it is a DOS-like path and it is denormalized', function () {
		expect(Path::new('c:/\\')->isDOS)->toBeTrue();
		expect(Path::new('C:/\\Windows')->isDOS)->toBeTrue();
	});
	test('Should be false when it is a Unix-like path and it is normalized', function () {
		expect(Path::new('/')->isDOS)->toBeFalse();
		expect(Path::new('/var')->isDOS)->toBeFalse();
	});
	test('Should be false when it is a Unix-like path and it is denormalized', function () {
		expect(Path::new('\\/.//var')->isDOS)->toBeFalse();
	});
	test('Should be false when it is a relative path', function () {
		expect(Path::new('file.txt')->isDOS)->toBeFalse();
		expect(Path::new('vendor/autoload.php')->isDOS)->toBeFalse();
	});
});

describe('Path->isUnix', function () {
	test('Should be true when it is a Unix-like path and it is normalized', function () {
		expect(Path::new('/')->isUnix)->toBeTrue();
		expect(Path::new('\\')->isUnix)->toBeTrue();
		expect(Path::new('/usr')->isUnix)->toBeTrue();
		expect(Path::new('\\usr/bin')->isUnix)->toBeTrue();
	});
	test('Should return true when it is a Unix-like path and it is normalized', function () {
		expect(Path::new('/\\')->isUnix)->toBeTrue();
		expect(Path::new('/\\')->isUnix)->toBeTrue();
		expect(Path::new('/usr//')->isUnix)->toBeTrue();
		expect(Path::new('\\usr\\/bin')->isUnix)->toBeTrue();
	});
	test('Should be false when it is a DOS-like path and it is normalized', function () {
		expect(Path::new('C:')->isUnix)->toBeFalse();
		expect(Path::new('c:')->isUnix)->toBeFalse();
		expect(Path::new('c:\\Windows')->isUnix)->toBeFalse();
		expect(Path::new('C:/Windows')->isUnix)->toBeFalse();
	});
	test('Should be false when it is a DOS-like path and it is denormalized', function () {
		expect(Path::new('c:/\\')->isUnix)->toBeFalse();
		expect(Path::new('C:/\\Windows')->isUnix)->toBeFalse();
	});
	test('Should be false when it is relative path', function () {
		expect(Path::new('filename.txt')->isUnix)->toBeFalse();
		expect(Path::new('.git/hooks')->isUnix)->toBeFalse();
	});
});

describe('Path->isRoot', function () {
	test('Should be true when it is drive', function () {
		expect(Path::new('C:')->isRoot)->toBeTrue();
		expect(Path::new('c:')->isRoot)->toBeTrue();
		expect(Path::new('C:\\')->isRoot)->toBeTrue();
		expect(Path::new('C:/')->isRoot)->toBeTrue();
	});
	test('Should be true when it is slash', function () {
		expect(Path::new('/')->isRoot)->toBeTrue();
		expect(Path::new('\\')->isRoot)->toBeTrue();
		expect(Path::new('\\/')->isRoot)->toBeTrue();
		expect(Path::new('/\\')->isRoot)->toBeTrue();
	});
	test('Should be false when it is an absolute path and is not a root', function () {
		expect(Path::new('C:/Windows')->isRoot)->toBeFalse();
		expect(Path::new('/var')->isRoot)->toBeFalse();
	});
	test('Should be false when it is a relative path', function () {
		expect(Path::new('file.txt')->isRoot)->toBeFalse();
		expect(Path::new('vendor/autoload.php')->isRoot)->toBeFalse();
	});
});

describe('Path->isAbsolute', function () {
	test('Should be true when the path is root', function () {
		expect(Path::new('C:')->isAbsolute)->toBeTrue();
		expect(Path::new('C:/')->isAbsolute)->toBeTrue();
		expect(Path::new('C:\\/')->isAbsolute)->toBeTrue();
		expect(Path::new('/')->isAbsolute)->toBeTrue();
		expect(Path::new('\\/')->isAbsolute)->toBeTrue();
	});
	test('Should be true when the path is absolute and normalized', function () {
		expect(Path::new('c:\\Windows\\Users')->isAbsolute)->toBeTrue();
		expect(Path::new('/usr/www/root')->isAbsolute)->toBeTrue();
	});
	test('Should be true when the path is absolute and denormalized', function () {
		expect(Path::new('c:\\\\Windows\\Users\\')->isAbsolute)->toBeTrue();
		expect(Path::new('/usr////www/root///')->isAbsolute)->toBeTrue();
	});
	test('Should be false when the path is relative and normalized', function () {
		expect(Path::new('vendor/autoload.php')->isAbsolute)->toBeFalse();
		expect(Path::new('users\\Admin')->isAbsolute)->toBeFalse();
	});
	test('Should be false when the path is relative and denormalized', function () {
		expect(Path::new('vendor///autoload.php')->isAbsolute)->toBeFalse();
		expect(Path::new('users\\\\Admin\\')->isAbsolute)->toBeFalse();
	});
	test('Should be false for current directory', function () {
		expect(Path::new('.')->isAbsolute)->toBeFalse();
	});
	test('Should be false for parent directory', function () {
		expect(Path::new('..')->isAbsolute)->toBeFalse();
	});
	test('Should be false when the path starts with a current directory', function () {
		expect(Path::new('./vendor/autoload.php')->isAbsolute)->toBeFalse();
		expect(Path::new('.\users\\Admin')->isAbsolute)->toBeFalse();
	});
	test('Should be false then the path starts with a parent directory', function () {
		expect(Path::new('..\\vendor///autoload.php')->isAbsolute)->toBeFalse();
		expect(Path::new('../users\\\\Admin\\')->isAbsolute)->toBeFalse();
	});
});

describe('Path->isRelative', function () {
	test('Should be false when the path is root', function () {
		expect(Path::new('C:')->isRelative)->toBeFalse();
		expect(Path::new('c:/')->isRelative)->toBeFalse();
		expect(Path::new('c:\\')->isRelative)->toBeFalse();
		expect(Path::new('/')->isRelative)->toBeFalse();
		expect(Path::new('\\')->isRelative)->toBeFalse();
	});
	test('Should be false when the path is absolute', function () {
		expect(Path::new('C:\\Windows\\')->isRelative)->toBeFalse();
		expect(Path::new('c:/users/')->isRelative)->toBeFalse();
		expect(Path::new('/usr/bin')->isRelative)->toBeFalse();
	});
	test('Should be true when the path is relative', function () {
		expect(Path::new('file.txt')->isRelative)->toBeTrue();
		expect(Path::new('vendor/autoload.php')->isRelative)->toBeTrue();
		expect(Path::new('vendor/phpunit\\\\phpunit/')->isRelative)->toBeTrue();
	});
	test('Should be true when the path is a current directory', function () {
		expect(Path::new('.')->isRelative)->toBeTrue();
	});
	test('Should be true when the path is a parent directory', function () {
		expect(Path::new('..')->isRelative)->toBeTrue();
	});
	test('Should be true when the path starts with a current directory', function () {
		expect(Path::new('./vendor')->isRelative)->toBeTrue();
	});
	test('Should be true when the path starts with a parent directory', function () {
		expect(Path::new('..\\users')->isRelative)->toBeTrue();
	});
});

describe('Path::__toString()', function () {
	test('Should return normalized path', function () {
		expect((string) Path::new('c:\\Windows///Users/./Admin/..\\\\Admin/'))->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Admin');
		expect((string) Path::new('\\var///www/./html/..\\\\public/'))->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'public');
		expect((string) Path::new('..'))->toBe('..');
		expect((string) Path::new('var/..\\..'))->toBe('..');
	});
});

describe('Path::equals()', function () {
	test('Should return true for equal normalized paths', function () {
		expect(Path::new('.')->equals('.'))->toBeTrue();
		expect(Path::new('file.txt')->equals('file.txt'))->toBeTrue();
		expect(Path::new('vendor')->equals('vendor'))->toBeTrue();
		expect(Path::new('C:\\Windows\\Users\\Admin')->equals('C:\\Windows\\Users\\Admin'))->toBeTrue();
		expect(Path::new('/var/www/html')->equals('/var/www/html'))->toBeTrue();
	});
	test('Should return true for equal denormalized paths', function () {
		expect(Path::new('./vendor/.\\\\../vendor/autoload.php')->equals('vendor\\\\autoload.php'))->toBeTrue();
		expect(Path::new('C:\\Windows\\.././Windows\\Users/./Admin/')->equals('C:/Windows/Users/Admin'))->toBeTrue();
		expect(Path::new('\\var\\.././var\\www/./html/')->equals('/var////www\\html'))->toBeTrue();
	});
	test('Should return false for unequal paths', function () {
		expect(Path::new('file.txt')->equals('vendor'))->toBeFalse();
		expect(Path::new('C:/Windows/Users')->equals('C:\\Windows\\Fonts'))->toBeFalse();
		expect(Path::new('/var/www/html\\project\\')->equals('\\var\\www\\html///'))->toBeFalse();
	});
	test('Should always return false for non-path instances', function () {
		expect(Path::new('.')->equals(new stdClass))->toBeFalse();
	});
	test('Should always return false for null', function () {
		expect(Path::new('.')->equals(null))->toBeFalse();
	});
});

describe('Path::getParent()', function () {
	test('Should return null when the path is root', function () {
		expect(Path::new('/')->getParent())->toBeNull();
		expect(Path::new('\\')->getParent())->toBeNull();
		expect(Path::new('C:')->getParent())->toBeNull();
		expect(Path::new('c:/')->getParent())->toBeNull();
		expect(Path::new('c:\\\\')->getParent())->toBeNull();
		expect(Path::new('c:\\\\Windows/..')->getParent())->toBeNull();
		expect(Path::new('/var/www/..\\..')->getParent())->toBeNull();
	});
	test('Should return null when the path is a current directory', function () {
		expect(Path::new('.')->getParent())->toBeNull();
	});
	test('Should return null when the path is relative and single', function () {
		expect(Path::new('vendor')->getParent())->toBeNull();
		expect(Path::new('file.txt')->getParent())->toBeNull();
		expect(Path::new('vendor//..')->getParent())->toBeNull();
		expect(Path::new('vendor\\bin\\../..')->getParent())->toBeNull();
	});
	test('Should return root when the path is absolute and single', function () {
		expect(Path::new('C:/Windows')->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::new('/var')->getParent()->path)->toBe(DIRECTORY_SEPARATOR);
	});
	test('Should return correct result when the path is absolute', function () {
		expect(Path::new('C:\\Windows\\Users\\Admin')->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users');
		expect(Path::new('C:\\Windows\\Users\\Admin')->getParent()->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::new('C:\\Windows/./././../Windows\\Users\\Admin')->getParent()->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::new('/var/www/html/project')->getParent()->getParent()->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www');
		expect(Path::new('/var/././../var/www/html/project')->getParent()->getParent()->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www');
	});
	test('Should return correct result when the path is relative', function () {
		expect(Path::new('vendor/bin/phpunit')->getParent()->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::new('Users\\Downloads\\./..\\Downloads///file.txt')->getParent()->path)->toBe('Users' . DIRECTORY_SEPARATOR . 'Downloads');
	});
});

describe('Path::toAbsolute()', function () {
	test('Should throw an exception when the base path is relative', function () {
		expect(fn () => Path::new('.')->toAbsolute('usr/bin'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'.\' to absolute: the base \'usr' . DIRECTORY_SEPARATOR . 'bin\' is not absolute');
	});
	test('Should return a root when there are too many parent jumps', function () {
		expect(Path::new('vendor/../../..')->toAbsolute('C:\\Windows\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
	});
	test('Should return the path itself when it is already absolute', function () {
		expect(Path::new('/usr/bin')->toAbsolute('C:\\Windows')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::new('C:\\Windows')->toAbsolute('/usr/bin')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
	});
	test('Should return correct result when the base is root', function () {
		expect(Path::new('vendor/autoload.php')->toAbsolute('C:')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect(Path::new('vendor/autoload.php')->toAbsolute('C:\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
		expect(Path::new('vendor/autoload.php')->toAbsolute('/')->path)->toBe(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
	});
	test('Should return the base path when the current one is a current directory', function () {
		expect(Path::new('.')->toAbsolute('/')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::new('.')->toAbsolute('c:')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::new('.')->toAbsolute('C:\\Windows')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::new('.')->toAbsolute('/usr//\\bin/.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should return a parent of the base when the current one is a parent directory', function () {
		expect(Path::new('..')->toAbsolute('C:\\Windows')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::new('..')->toAbsolute('/usr//\\bin/.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr');
		expect(Path::new('..')->toAbsolute('C:\\Windows\\Users')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::new('..')->toAbsolute('/usr//\\bin/php')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should correctly jump out when the current path contains enough parent jumps', function () {
		expect(Path::new('vendor/../../..')->toAbsolute('C:\\Windows\\Users\\Downloads')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
	});
	test('Should return an absolute path', function () {
		expect(Path::new('vendor/autoload.php')->toAbsolute('C:\\inetpub\\wwwroot\\project')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'inetpub' . DIRECTORY_SEPARATOR . 'wwwroot' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
	});
});

describe('Path::toRelative()', function () {
	test('Should throw an exception when the base path is relative', function () {
		expect(fn () => Path::new('/usr/bin')->toRelative('home'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin\' to relative: the base \'home\' is not absolute');
		expect(fn () => Path::new('C:\\Windows\\')->toRelative('home'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'C:' . DIRECTORY_SEPARATOR . 'Windows\' to relative: the base \'home\' is not absolute');
	});
	test('Should throw an exception when the base path is not a parent of the current path', function () {
		expect(fn () => Path::new('/usr/bin')->toRelative('/home'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin\' to relative: the base \'' . DIRECTORY_SEPARATOR . 'home\' is not a parent of the path');
		expect(fn () => Path::new('C:\\Windows\\Users')->toRelative('D:\\Games'))->toThrow(InvalidArgumentException::class, 'Cannot convert the path \'C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users\' to relative: the base \'D:' . DIRECTORY_SEPARATOR . 'Games\' is not a parent of the path');
	});
	test('Should return correct result when the base is an absolute path', function () {
		expect(Path::new('C:\\Windows\\Users/Admin\\Downloads/file.txt')->toRelative('c:/Windows/Users/Admin')->path)->toBe('Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect(Path::new('/var/www/html\\project/public\\index.php')->toRelative('\\var/www/html/project')->path)->toBe('public' . DIRECTORY_SEPARATOR . 'index.php');
	});
	test('Should return correct result when the base is a root path', function () {
		expect(Path::new('C:\\Windows\\Users/Admin\\Downloads/file.txt')->toRelative('C:')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect(Path::new('/var/www/html\\project/public\\index.php')->toRelative('\\')->path)->toBe('var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php');
	});
	test('Should return the path itself when the path is already relative', function () {
		expect(Path::new('Downloads\\file.txt')->toRelative('C:')->path)->toBe('Downloads' . DIRECTORY_SEPARATOR . 'file.txt');
		expect(Path::new('public/index.php')->toRelative('/var/www/html/project')->path)->toBe('public' . DIRECTORY_SEPARATOR . 'index.php');
	});
});

describe('Path::format()', function () {
	test('Should throw an error when the path separator is not a slash', function () {
		expect(fn () => Path::new('vendor/bin')->format(['separator' => '.']))->toThrow(InvalidArgumentException::class, 'Cannot format a path: invalid separator \'.\'. Only \'\\\', \'/\' characters are allowed');
	});
	test('Should return platform-dependent result when no parameters is passed', function () {
		expect(Path::new('vendor/\\bin')->format())->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should use specified separator', function () {
		expect(Path::new('vendor\\/bin')->format([Path::OPTKEY_SEPARATOR => '\\']))->toBe('vendor\\bin');
		expect(Path::new('vendor\\/bin')->format([Path::OPTKEY_SEPARATOR => '/']))->toBe('vendor/bin');
	});
	test('Should append a slash at the end when it is explicitly defined', function () {
		expect(Path::new('vendor/bin')->format([Path::OPTKEY_TRAILING_SLASH => true]))->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR);
	});
});

describe('Path::join()', function () {
	test('Should keep parent jumps', function () {
		expect(Path::join('..')->path)->toBe('..');
		expect(Path::join('..', '..')->path)->toBe('..' . DIRECTORY_SEPARATOR . '..');
	});
	test('Should return a current directory when no arguments were passed', function () {
		expect(Path::join()->path)->toBe('.');
	});
	test('Should return the first path when the next ones are current directories', function () {
		expect(Path::join('vendor/bin', '.')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::join('vendor/bin', '.', '.')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should return the first path when the first one is root and the next ones are current directories', function () {
		expect(Path::join('/', '.')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::join('\\', '.', '.')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::join('C:', '.')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::join('C:/', '.', '.')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
	});
	test('Should return a parent of the first path when the next ones are parent directories', function () {
		expect(Path::join('vendor/bin', '..')->path)->toBe('vendor');
		expect(Path::join('C:\\Windows/Users/Admin', '..', '..')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
	});
	test('Should correctly jump to the previous path when next paths contain parent jumps', function () {
		expect(Path::join('C:\\Windows\\Users/Admin', 'Downloads/../Documents')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Documents');
	});
	test('Should return a normalized path when the passed paths are denormalized', function () {
		expect(Path::join('/var/www\\\\/html', 'project', 'App/./.\\../App', 'Kernel')->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Kernel');
	});
	test('Should correctly concatenate paths when the next paths are absolute ones', function () {
		expect(Path::join('vendor', '/bin', '\\phpunit')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit');
	});
	test('Should correctly concatenate paths when the first one is relative', function () {
		expect(Path::join('vendor', 'bin')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should correctly concatenate paths when the first one is absolute', function () {
		expect(Path::join('/var', 'www/html')->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html');
	});
});

describe('Path::expand()', function () {
	test('Should return only normalized path itself when there is no variables to expand', function () {
		expect(Path::expand('vendor/.\\\\bin/')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should expand windows-like variable when the name has the same casing as the real variable', function () {
		expect(Path::expand('%GLOBAL_VARIABLE%')->path)->toBe(getenv('GLOBAL_VARIABLE', true));
		expect(Path::expand('%GLOBAL_VARIABLE%/Users')->path)->toBe(getenv('GLOBAL_VARIABLE', true) . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand windows-like variable when the name has different casing than the real variable', function () {
		expect(Path::expand('%GLOBAL_VARIABLE%')->path)->toBe(getenv('GLOBAL_VARIABLE', true));
		expect(Path::expand('%GLOBAL_VARIABLE%/Users')->path)->toBe(getenv('GLOBAL_VARIABLE', true) . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand windows-like variable when the name has the same casing as the overriden variable', function () {
		expect(Path::expand('%varname%', ['varname' => 'C:\\Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::expand('%varname%/Users', ['varname' => 'C:/Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand windows-like variable when the name has different casing than the overriden variable', function () {
		expect(Path::expand('%VARNAME%', ['varname' => 'C:\\Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::expand('%VARNAME%/Users', ['varname' => 'C:/Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand windows-like variable to an empty string when there is no a variable with such a name', function () {
		expect(Path::expand('%VARNAME%')->path)->toBe('.');
		expect(Path::expand('%VARNAME%/Users')->path)->toBe(DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand unix-like variable when the name has the same casing as the real variable', function () {
		expect(Path::expand('$GLOBAL_VARIABLE')->path)->toBe(getenv('GLOBAL_VARIABLE', true));
		expect(Path::expand('$GLOBAL_VARIABLE/Users')->path)->toBe(getenv('GLOBAL_VARIABLE', true) . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand unix-like variable to an empty string when the name has different casing than the real variable', function () {
		expect(Path::expand('$global_variable')->path)->toBe('.');
		expect(Path::expand('$global_variable/Users')->path)->toBe(DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand unix-like variable when the name has the same casing as the overriden variable', function () {
		expect(Path::expand('$varname', ['varname' => 'C:\\Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::expand('$varname/Users', ['varname' => 'C:/Windows'])->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand unix-like variable to an empty string when the name has different casing than the overriden variable', function () {
		expect(Path::expand('$VARNAME', ['varname' => 'C:\\Windows'])->path)->toBe('.');
		expect(Path::expand('$VARNAME/Users', ['varname' => 'C:/Windows'])->path)->toBe(DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand unix-like variable to an empty string when there is no a variable with such a name', function () {
		expect(Path::expand('$VARNAME')->path)->toBe('.');
		expect(Path::expand('$VARNAME/Users')->path)->toBe(DIRECTORY_SEPARATOR . 'Users');
	});
	test('Should expand multiple variables', function () {
		expect(Path::expand('%global_variable%/$varname', ['varname' => 'admin'])->path)->toBe(getenv('global_variable') . DIRECTORY_SEPARATOR . 'admin');
	});
	test('Should expand ~ Unix symbol', function () {})->skip();
});

describe('Path::normalize()', function () {
	// Empty string
	test('Should return a current directory when the string is empty', function () {
		expect(Path::normalize('')->path)->toBe('.');
	});
	// Current directory
	test('Should return a current directory when a current directory is passed', function () {
		expect(Path::normalize('.')->path)->toBe('.');
		expect(Path::normalize('./')->path)->toBe('.');
		expect(Path::normalize('.\\')->path)->toBe('.');
	});
	test('Should return a current directory when there are many current directories', function () {
		expect(Path::normalize('./.')->path)->toBe('.');
		expect(Path::normalize('.\\.')->path)->toBe('.');
	});
	test('Should return a current directory when there are enough parent jumps and the path is relative', function () {
		expect(Path::normalize('vendor/..')->path)->toBe('.');
		expect(Path::normalize('vendor/bin\\../..')->path)->toBe('.');
	});
	test('Should return \'.\' when the resulting path is a current directory', function () {
		expect(Path::normalize('vendor/..\\bin/..\\')->path)->toBe('.');
		expect(Path::normalize('vendor/bin/..\\..')->path)->toBe('.');
	});
	// Parent directory
	test('Should return a parent directory when a parent directory is passed', function () {
		expect(Path::normalize('..')->path)->toBe('..');
		expect(Path::normalize('../')->path)->toBe('..');
		expect(Path::normalize('..\\')->path)->toBe('..');
	});
	test('Should return a parent directory when there are many parent directories', function () {
		expect(Path::normalize('../..')->path)->toBe('..' . DIRECTORY_SEPARATOR . '..');
		expect(Path::normalize('..\\..')->path)->toBe('..' . DIRECTORY_SEPARATOR . '..');
	});
	test('Should return a parent directory when there are enough parent jumps and the path is relative', function () {
		expect(Path::normalize('vendor/bin/../../..')->path)->toBe('..');
	});
	test('Should return many parent jumps when there are many parent jumps and the path is relative', function () {
		expect(Path::normalize('vendor/bin/../../..\\..')->path)->toBe('..' . DIRECTORY_SEPARATOR . '..');
	});
	test('Should collapse parent directory parts', function () {
		expect(Path::normalize('usr/..')->path)->toBe('.');
		expect(Path::normalize('usr/../home')->path)->toBe('home');
		expect(Path::normalize('usr/../home/..')->path)->toBe('.');
		expect(Path::normalize('usr/../home/user/../admin')->path)->toBe('home' . DIRECTORY_SEPARATOR . 'admin');
	});
	// Root
	test('Should keep only the root when there are jumps out of root', function () {
		expect(Path::normalize('c:\\..')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('c:\\../.')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('/..')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::normalize('/..\\..\\')->path)->toBe(DIRECTORY_SEPARATOR);
		expect(Path::normalize('c:\\../Windows')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect(Path::normalize('/..\\..\\var')->path)->toBe(DIRECTORY_SEPARATOR . 'var');
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
	// Relative
	test('Should return the string itself when it is a single name', function () {
		expect(Path::normalize('file.txt')->path)->toBe('file.txt');
		expect(Path::normalize('vendor')->path)->toBe('vendor');
		expect(Path::normalize('.git')->path)->toBe('.git');
	});
	test('Should correctly normalize relative paths', function () {
		expect(Path::normalize('Windows/Fonts')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('./Windows/..\\\\/Windows/./Fonts\\')->path)->toBe('Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('usr/bin\\\\php')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect(Path::normalize('./usr/../usr/bin\\\\php\\.')->path)->toBe('usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	// Absolute
	test('Should correctly normalize absolute paths', function () {
		expect(Path::normalize('c:\\Windows/Fonts')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('c:\\./Windows/..\\\\/Windows/./Fonts\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Fonts');
		expect(Path::normalize('\\usr/bin\\\\php')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
		expect(Path::normalize('/./usr/../usr/bin\\\\php\\.')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php');
	});
	// Separators
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
	// Drive letters
	test('Should capitalize drive letters', function () {
		expect(Path::normalize('c:\\')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect(Path::normalize('c:/')->path)->toBe('C:' . DIRECTORY_SEPARATOR);
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
});

describe('Path::new()', function () {
	test('Should set the path to a current directory when an empty string is passed', function () {
		$p = Path::new('');
		expect($p->path)->toBe('.');
	});
});

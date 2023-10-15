<?php
namespace Stein197\FileSystem;

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
		expect((string) new Path('\\var///www/./html/..\\\\public/'))->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'public');
	});
	test('Should return the initial path if the normalization cannot be performed', function () {
		expect((string) new Path('..'))->toBe('..');
		expect((string) new Path('var/..\\..'))->toBe('var/..\\..');
	});
});

describe('Path::equals()', function () {
	test('Should return true for equal normalized paths', function () {
		expect((new Path('.'))->equals('.'))->toBeTrue();
		expect((new Path('file.txt'))->equals('file.txt'))->toBeTrue();
		expect((new Path('vendor'))->equals('vendor'))->toBeTrue();
		expect((new Path('C:\\Windows\\Users\\Admin'))->equals('C:\\Windows\\Users\\Admin'))->toBeTrue();
		expect((new Path('/var/www/html'))->equals('/var/www/html'))->toBeTrue();
	});
	test('Should return true for equal denormalized paths', function () {
		expect((new Path('./vendor/.\\\\../vendor/autoload.php'))->equals('vendor\\\\autoload.php'))->toBeTrue();
		expect((new Path('C:\\Windows\\.././Windows\\Users/./Admin/'))->equals('C:/Windows/Users/Admin'))->toBeTrue();
		expect((new Path('\\var\\.././var\\www/./html/'))->equals('/var////www\\html'))->toBeTrue();
	});
	test('Should return false for unequal paths', function () {
		expect((new Path('file.txt'))->equals('vendor'))->toBeFalse();
		expect((new Path('C:/Windows/Users'))->equals('C:\\Windows\\Fonts'))->toBeFalse();
		expect((new Path('/var/www/html\\project\\'))->equals('\\var\\www\\html///'))->toBeFalse();
	});
	test('Should always return false for non-path instances', function () {
		expect((new Path('.'))->equals(new stdClass))->toBeFalse();
	});
	test('Should always return false for null', function () {
		expect((new Path('.'))->equals(null))->toBeFalse();
	});
});

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

describe('Path::getParent()', function () {
	test('Should return null when the path is root', function () {
		expect((new Path('/'))->getParent())->toBeNull();
		expect((new Path('\\'))->getParent())->toBeNull();
		expect((new Path('C:'))->getParent())->toBeNull();
		expect((new Path('c:/'))->getParent())->toBeNull();
		expect((new Path('c:\\\\'))->getParent())->toBeNull();
		expect((new Path('c:\\\\Windows/..'))->getParent())->toBeNull();
		expect((new Path('/var/www/..\\..'))->getParent())->toBeNull();
	});
	test('Should return null when the path is a current directory', function () {
		expect((new Path('.'))->getParent())->toBeNull();
	});
	test('Should return null when the path is relative and single', function () {
		expect((new Path('vendor'))->getParent())->toBeNull();
		expect((new Path('file.txt'))->getParent())->toBeNull();
		expect((new Path('vendor//..'))->getParent())->toBeNull();
		expect((new Path('vendor\\bin\\../..'))->getParent())->toBeNull();
	});
	test('Should return root when the path is absolute and single', function () {
		expect((new Path('C:/Windows'))->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR);
		expect((new Path('/var'))->getParent()->path)->toBe(DIRECTORY_SEPARATOR);
	});
	test('Should return correct result when the path is absolute', function () {
		expect((new Path('C:\\Windows\\Users\\Admin'))->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows' . DIRECTORY_SEPARATOR . 'Users');
		expect((new Path('C:\\Windows\\Users\\Admin'))->getParent()->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect((new Path('C:\\Windows/./././../Windows\\Users\\Admin'))->getParent()->getParent()->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
		expect((new Path('/var/www/html/project'))->getParent()->getParent()->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html');
		expect((new Path('/var/././../var/www/html/project'))->getParent()->getParent()->path)->toBe(DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'html');
	});
	test('Should return correct result when the path is relative', function () {
		expect((new Path('vendor/bin/phpunit'))->getParent()->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect((new Path('Users\\Downloads\\./..\\Downloads///file.txt'))->getParent()->path)->toBe('Users' . DIRECTORY_SEPARATOR . 'Downloads');
	});
});

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
		expect((new Path('/usr/bin'))->toAbsolute('C:\\Windows')->path)->toBe(DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'bin');
		expect((new Path('C:\\Windows'))->toAbsolute('/usr/bin')->path)->toBe('C:' . DIRECTORY_SEPARATOR . 'Windows');
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

describe('Path::format()', function () {
	test('Should throw an error when the path separator is not a slash', function () {
		expect(fn () => (new Path('vendor/bin'))->format(['separator' => '.']))->toThrow(InvalidArgumentException::class, 'Cannot format a path: invalid separator \'.\'. Only \'\\\', \'/\' characters are allowed');
	});
	test('Should return platform-dependent result when no parameters is passed', function () {
		expect((new Path('vendor/\\bin'))->format())->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
	});
	test('Should use specified separator', function () {
		expect((new Path('vendor\\/bin'))->format([Path::OPTKEY_SEPARATOR => '\\']))->toBe('vendor\\bin');
		expect((new Path('vendor\\/bin'))->format([Path::OPTKEY_SEPARATOR => '/']))->toBe('vendor/bin');
	});
	test('Should append a slash at the end when it is explicitly defined', function () {
		expect((new Path('vendor/bin'))->format([Path::OPTKEY_TRAILING_SLASH => true]))->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR);
	});
});

describe('Path::join()', function () {
	test('Should throw an exception when there are too many parent jumps', function () {
		expect(fn () => Path::join('..'))->toThrow(InvalidArgumentException::class, 'Cannot normalize the path \'..\': too many parent jumps');
	});
	test('Should return a current directory when no arguments were passed', function () {
		expect(Path::join()->path)->toBe('.');
	});
	test('Should return the first path when the next ones are current directories', function () {
		expect(Path::join('vendor/bin', '.')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
		expect(Path::join('vendor/bin', '.', '.')->path)->toBe('vendor' . DIRECTORY_SEPARATOR . 'bin');
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
	test('Should expand ~ Unix symbol', function () {})->skip();
});

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
});

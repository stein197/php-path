<?php
namespace Stein197\FileSystem;

use Stein197\Equalable;
use InvalidArgumentException;
use Stringable;
use function array_map;
use function array_merge;
use function array_pop;
use function array_search;
use function is_string;
use function getenv;
use function join;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
use function sizeof;
use function sprintf;
use function str_starts_with;
use function strpos;
use function strtolower;
use function strtoupper;
use function strval;
use function trim;
use const DIRECTORY_SEPARATOR;

/**
 * The class provides means to simplify work with paths (strings like 'C:\Windows\System32', '/usr/bin',
 * 'vendor/autoload.php' etc.). The class contains functions that help normalize, join, format paths and so on.
 * All paths fall into three categories:
 * - Absolute
 * - Relative
 * - Root
 * 
 * Absolute paths are those that start with:
 * - slash (`\` or `/`)
 * - drive (`C:`, `D:`, etc.)
 * - drive and slash (`C:\`, `D:/`, etc.)
 * 
 * Other ones are relative. Root paths are a special kind of paths that are absolute and contain only drive and/or slash
 * (`/`, `C:\`, `D:`)
 * 
 * When formatted, all paths have `DIRECTORY_SEPARATOR` separator and don't end with a trailing slash by default,
 * although it can be changed when passing an options object down to {@link \Stein197\FileSystem\Path::format()} function.
 * 
 * The class also implements `Stringable` interface, so the instance of this class can be safely passed to functions
 * that expect strings as a parameter. When casted to string, a normalized path is returned. If the normalization cannot
 * be performed, then the initial string passed to the constructor will be returned.
 * 
 * `__toString()` returns normalized path.
 * 
 * All methods that return a path, always return a normalized one.
 */
// TODO: Implement methods: getDepth(), getElement(), getSubpath(), startsWith(), endsWith(), toArray()?
// TODO: Implement interfaces: Traversable, Iterator, ArrayAccess, Serializable, Generator, Countable
// TODO: Make the path immutable (all methods must return a new instance)
// TODO: Delete exception in case if the amount of jumps exceeds limit
class Path implements Stringable, Equalable {

	/**
	 * Which separator to use when formatting a path. Allowed values are '\\' and '/', otherwise an error is thrown.
	 * `DIRECTORY_SEPARATOR` by default.
	 * ```php
	 * // An example
	 * (new Path('/a/b'))->format([Path::OPTKEY_SEPARATOR => '\\']); // '\\a\\b'
	 * (new Path('/a/b'))->format([Path::OPTKEY_SEPARATOR => ' ']);  // an error
	 * ```
	 */
	public const OPTKEY_SEPARATOR = 'separator';

	/**
	 * Use trailing slash when formatting a path.
	 * `false` by default.
	 * ```php
	 * // An example
	 * (new Path('/a/b'))->format([Path::OPTKEY_TRAILING_SLASH => true]);  // '/a/b/'
	 * (new Path('/a/b'))->format([Path::OPTKEY_TRAILING_SLASH => false]); // '/a/b'
	 * ```
	 */
	public const OPTKEY_TRAILING_SLASH = 'trailingSlash';

	private const REGEX_SLASH = '/[\\\\\/]+/';
	private const REGEX_ABS_DOS = '/^(?:[a-z]:)[\\\\\/]?/i';
	private const REGEX_ABS_UNIX = '/^[\\\\\/]/';
	private const REGEX_ROOT = '/^(?:[a-z]:)?[\\\\\/]*$/i';
	private const REGEX_ENV_VAR = '/%.+?%|\$.+?(?=[\/\\\\$]|$)/';
	private const DIR_CURRENT = '.';
	private const DIR_PARENT = '..';
	private const ALLOWED_SEPARATORS = ['\\', '/'];
	private const DEFAULT_OPTIONS = [
		self::OPTKEY_SEPARATOR => DIRECTORY_SEPARATOR,
		self::OPTKEY_TRAILING_SLASH => false
	];

	/**
	 * `true` if the path is DOS-like ('C:\\Windows' and so). It's known only if it's an absolute path, so it's always
	 * false if the path is relative.
	 * ```php
	 * (new Path('C:/Windows'))->isDOS; // true
	 * (new Path('/root'))->isDOS;      // false
	 * (new Path('file.txt'))->isDOS;   // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isDOS;

	/**
	 * `true` if the path is Unix-like ('/var' and so). It's known only if it's an absolute path, so it's always false
	 * if the path is relative.
	 * ```php
	 * (new Path('C:/Windows'))->isUnix; // false
	 * (new Path('/root'))->isUnix;      // true
	 * (new Path('file.txt'))->isUnix;   // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isUnix;

	/**
	 * `true` if the path is root. Root paths are a special case of absolute ones since they also start with a slash or
	 * a drive letter, but they denote only a root folder (like '/', 'C:\').
	 * ```php
	 * (new Path('C:\\'))->isRoot;     // true
	 * (new Path('/'))->isRoot;        // true
	 * (new Path('/usr/bin'))->isRoot; // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isRoot;

	/**
	 * `true` if the path is absolute. Absolute paths are those that start with slashes ('/usr', '\\root') or a drive
	 * letter ('C:', 'C:/', 'C:\\'). It's the opposite of `isRelative`.
	 * ```php
	 * // An example
	 * (new Path('C:\\Windows'))->isAbsolute;         // true
	 * (new Path('/usr/bin'))->isAbsolute;            // true
	 * (new Path('vendor/autoload.php'))->isAbsolute; // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isAbsolute;

	/**
	 * `true` if the path is relative. Relative paths are those that don't start with slashes or drive letter
	 * ('node_modules', 'public/index.php'). It's the opposite of `isAbsolute`.
	 * ```php
	 * (new Path('C:\\Windows'))->isRelative;         // false
	 * (new Path('/usr/bin'))->isRelative;            // false
	 * (new Path('vendor/autoload.php'))->isRelative; // true
	 * ```
	 * @var bool
	 */
	public readonly bool $isRelative;

	/**
	 * Raw path string that was passed to the constructor. When a path object was created by a direct call to the
	 * constructor, then it holds what was passed to the constructor. Other methods that return `Path` instance will
	 * always have normalized `$path` property.
	 * ```php
	 * // An example
	 * $p = new Path('/a/b\\c/');
	 * $p->format(); // could be '/a/b/c'
	 * $p->path;      // always '/a/b\\c/'
	 * ```
	 * @var string
	 */
	public readonly string $path;

	/**
	 * Create a new path object.
	 * @param string|self $data A string or another path object.
	 */
	public function __construct(string | self $data) {
		$this->path = strval($data);
		if (!$this->path)
			$this->path = '.';
		$this->isDOS = self::isDOS($this->path);
		$this->isUnix = self::isUnix($this->path);
		$this->isRoot = !!preg_match(self::REGEX_ROOT, $this->path);
		$this->isAbsolute = $this->isDOS || $this->isUnix;
		$this->isRelative = !$this->isAbsolute;
	}

	public function __toString(): string {
		try {
			return $this->format(self::DEFAULT_OPTIONS);
		} catch (InvalidArgumentException) {
			return $this->path;
		}
	}

	/**
	 * Check if the path is equal to another one. Paths are equal when their normalized versions match against each
	 * other.
	 * @param mixed $path Path to check against.
	 * @return bool `true` if both paths are equal.
	 */
	public function equals($path): bool {
		try {
			return ($path instanceof self || is_string($path)) && self::normalize($this)->path === self::normalize($path)->path;
		} catch (InvalidArgumentException) {
			return false;
		}
	}

	/**
	 * Get parent of the path. If the path is root then `null` is returned.
	 * @return null|Path Parent path or `null` if the path is root or if it's relative one getting a parent is not 
	 *                   possible.
	 * @throws InvalidArgumentException If normalization cannot be performed on the path.
	 * ```php
	 * // An example
	 * (new Path('/usr/bin'))->getParent(); // Path('/usr')
	 * (new Path('C:'))->getParent();       // null
	 * (new Path('vendor'))->getParent();   // null
	 * ```
	 */
	public function getParent(): ?self {
		$normalized = self::normalize($this);
		if ($normalized->isRoot)
			return null;
		$hasParent = sizeof(self::split($normalized->path)) > 1;
		return $hasParent ? self::normalize(preg_replace('/[^\\\\\/]+$/', '', $normalized->path)) : null;
	}

	/**
	 * Normalize and convert the path to an absolute one. It's a concatenation of `$base` and the path itself. If the path is already
	 * absolute then the path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path to make this one absolute against.
	 * @return Path An absolute normalized path.
	 * @throws InvalidArgumentException If the base is not an absolute path.
	 * ```php
	 * // An example
	 * (new Path('file.txt'))->toAbsolute('C:\\Windows'); // Path('C:\\Windows\\file.txt')
	 * (new Path('/usr'))->toAbsolute('/home');           // Path('/usr')
	 * (new Path('/usr'))->toAbsolute('Windows');         // an exception
	 * ```
	 */
	public function toAbsolute(string | self $base): self {
		if ($this->isAbsolute)
			return clone $this;
		$base = $base instanceof self ? $base : new self($base);
		if (!$base->isAbsolute)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to absolute: the base '{$base->path}' is not absolute");
		return self::join($base->path, $this->path);
	}

	/**
	 * Normalize and convert the path to a relative one. It rips the `$base` component out of the path. If the path is already
	 * relative thethe path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path that will be ripped out of the path.
	 * @return Path A relative normalized path.
	 * @throws InvalidArgumentException If the base is not a relative path.
	 * ```php
	 * // An example
	 * (new Path('C:\\Windows\\file.txt'))->toRelative('C:/Windows'); // Path('file.txt')
	 * (new Path('file.txt'))->toRelative('C:/Windows');              // Path('file.txt')
	 * (new Path('file.txt'))->toRelative('config.json');             // an exception
	 * ```
	 */
	public function toRelative(string | self $base): self {
		if ($this->isRelative)
			return clone $this;
		$base = $base instanceof self ? $base : new self($base);
		if (!$base->isAbsolute)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to relative: the base '{$base->path}' is not absolute");
		$thisFormat = $this->format();
		$baseFormat = $base->format();
		if (strpos($thisFormat, $baseFormat) !== 0)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to relative: the base '{$base->path}' is not a parent of the path");
		$result = substr($thisFormat, strlen($baseFormat));
		$result = ltrim($result, '\\/');
		return new self($result);
	}

	/**
	 * Format the path and return a string representation of it. The separator is `DIRECTORY_SEPARATOR` and
	 * 'trailingSlash' is false by default.
	 * @param array $options Options to use. See `OPTKEY_*` public constants for the documentation.
	 * @return string Formatted string.
	 * @throws InvalidArgumentException If the separator is not a slash.
	 * ```php
	 * // An example
	 * (new Path('c:/windows\\file.txt'))->format(['separator' => '\\', 'trailingSlash' => false]); // 'C:\\Windows\\file.txt'
	 * (new Path('\\usr\\bin'))->format(['separator' => '/', 'trailingSlash' => true]);             // '/usr/bin/'
	 * ```
	 */
	public function format(array $options = self::DEFAULT_OPTIONS): string {
		$options = array_merge(self::DEFAULT_OPTIONS, $options);
		self::checkOptions($options);
		$result = self::normalize($this->path)->path;
		$result = preg_replace(self::REGEX_SLASH, $options[self::OPTKEY_SEPARATOR], $result);
		$result .= $options[self::OPTKEY_TRAILING_SLASH] ? $options[self::OPTKEY_SEPARATOR] : '';
		return $result;
	}

	/**
	 * Concatenate the given paths (`DIRECTORY_SEPARATOR` is used as a separator) and normalize the result.
	 * @param string[] $data Paths to concatenate
	 * @return Path Concatenated path.
	 * @throws InvalidArgumentException If there are too many parent jumps.
	 * @uses \Stein197\Path::normalize()
	 * ```php
	 * // An example
	 * Path::join(__DIR__, 'vendor/bin', 'phpunit.bat'); // Path('/usr/www/vendor/bin/phpunit.bat')
	 * Path::join('../..', 'phpunit.bat');               // an exception
	 * ```
	 */
	public static function join(string ...$data): self {
		return self::normalize(join(self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR], $data));
	}

	/**
	 * Normalize a path and expand environment variables like '%SystemRoot%' for Windows and '$HOME', '~' for Unix.
	 * Windows-like variables enclosed within percent characters are considered as case-insensitive, while for Unix-like 
	 * ones considered as case-sensitive.
	 * @param string $path Path to expand variables within.
	 * @param array $env Override environment variables.
	 * @return Path Path with expanded variables.
	 * ```php
	 * // An example
	 * Path::expand('%SystemRoom%\\Downloads');                 // Path('C:\\Users\\Admin\\Downloads')
	 * Path::expand('$HOME\\bin');                              // Path('/home/admin/bin')
	 * Path::expand('~/downloads');                             // Path('/home/admin/downloads')
	 * Path::expand('$varname/admin', ['varname' => '/home/']); // Path(/home/admin')
	 * ```
	 */
	// TODO: Expand "~" symbol
	public static function expand(string | self $path, ?array $env = null): self {
		return self::normalize(preg_replace_callback(self::REGEX_ENV_VAR, function (array $matches) use ($env): string {
			[$match] = $matches;
			$isUnix = str_starts_with($match, '$');
			$name = trim($match, '%$');
			$env = array_merge(getenv(null, false), getenv(null, true), $env ?? []);
			if ($isUnix)
					return isset($env[$name]) ? $env[$name] : '';
			$name = strtolower($name);
			foreach ($env as $varName => $value)
				if ($name === strtolower($varName))
					return $value;
			return '';
		}, $path));
	}

	/**
	 * Normalize the given path. The next operations will be performed:
	 * - Removing of redundant slashes ('C:\\\\Windows\\file.txt' -> 'C:\\Windows\\file.txt')
	 * - Removing of current directory path ('./vendor' -> 'vendor')
	 * - Removing of parent jumps ('vendor/../public' -> 'public')
	 * - Unification of slashes (`DIRECTORY_SEPARATOR` is used, '\\usr/bin' -> '/usr/bin')
	 * - Trimming of trailing slashes ('/usr/bin/' -> '/usr/bin')
	 * - Capitalizing of drive letters ('c:/windows' -> 'C:/windows')
	 * 
	 * Note, that PHP's `realpath()` returns `false` if the path doesn't exist. `Path::normalize()` doesn't rely on a
	 * path existence, so an object is always returned.
	 * @param string|self $path Path to normalize. If it's an instance of `Path`, then a copy will be returned.
	 * @return self Normalized path.
	 * ```php
	 * Path::normalize('//usr\\bin/./..\\www\\'); // Path('/usr/www')
	 * Path::normalize('C:\\Windows\\..\\..');    // Path('C:\\')
	 * ```
	 */
	public static function normalize(string | self $path): self {
		if ($path instanceof self)
			return clone $path;
		$isAbsolute = self::isDOS($path) || self::isUnix($path);
		$data = self::split($path);
		$result = [];
		foreach ($data as $i => $part) {
			if ($part === self::DIR_CURRENT)
				continue;
			if ($part === self::DIR_PARENT) {
				if (!$result || $result[sizeof($result) - 1] === self::DIR_PARENT)
					$result[] = self::DIR_PARENT;
				else if (sizeof($result) === 1 && $isAbsolute)
					continue;
				else
					array_pop($result);
			} else {
				$result[] = !$i && preg_match(self::REGEX_ABS_DOS, $part) ? strtoupper($part) : $part;
			}
		}
		$separator = self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR];
		$result = join($separator, $result);
		if (!preg_match(self::REGEX_ROOT, $result))
			$result = rtrim($result, $separator);
		if (preg_match(self::REGEX_ROOT, $result) && preg_match(self::REGEX_ABS_DOS, $result))
			$result = rtrim($result, '\\/') . $separator;
		$isAbsolute = preg_match(self::REGEX_ABS_UNIX, $path) || preg_match(self::REGEX_ABS_DOS, $path);
		if (!$result)
			$result = $isAbsolute ? $separator : '.';
		return new self($result);
	}

	private static function split(string $path): array {
		return preg_split(self::REGEX_SLASH, $path);
	}

	private static function checkOptions(array $options): void {
		$isValidSeparator = array_search($options[self::OPTKEY_SEPARATOR], self::ALLOWED_SEPARATORS) !== false;
		if (!$isValidSeparator)
			throw new InvalidArgumentException(
				sprintf(
					"Cannot format a path: invalid separator '%s'. Only %s characters are allowed",
					$options[self::OPTKEY_SEPARATOR],
					join(
						', ',
						array_map(
							fn ($char): string => "'{$char}'",
							self::ALLOWED_SEPARATORS
						)
					)
				)
			);
	}

	private static function isDOS(string $path): bool {
		return !!preg_match(self::REGEX_ABS_DOS, $path);
	}

	private static function isUnix(string $path): bool {
		return !!preg_match(self::REGEX_ABS_UNIX, $path);
	}
}

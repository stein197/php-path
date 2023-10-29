<?php
namespace Stein197\FileSystem;

use Stein197\Equalable;
use ArrayAccess;
use Countable;
use Exception;
use InvalidArgumentException;
use Iterator;
use Stringable;
use function abs;
use function addslashes;
use function array_map;
use function array_merge;
use function array_pop;
use function array_search;
use function array_slice;
use function array_splice;
use function is_int;
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
use function trigger_error;
use function trim;
use const DIRECTORY_SEPARATOR;
use const E_USER_WARNING;

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
 * Root paths are a special kind of paths that are absolute and contain only drive and/or slash (`/`, `C:\`, `D:`).
 * Other ones are relative.
 * 
 * The main method to instantiate path objects is a `Path::new()` method. When instantiated, the provided string is
 * automatically normalized. All instances of this class are read-only, which means that every method that return a path
 * object always returns a new one.
 * 
 * Many methods work with indices. 0 index always points to the root, so for example for relative paths, the index 0 is
 * absent. The class also works with negative indices, in such case the counting starts from the end of the path (for
 * example index -1 points to the last part of the path).
 * 
 * The class implements the next interfaces:
 * - `ArrayAccess`. Allows access to single parts of a path. In attempt of setting something by index, an exception is
 * thrown
 * - `Countable`. Allows to pass an instance down to `sizeof()`/`count()` functions. Returns the same as `$depth`
 * property.
 * - `Iterator`. Allows to interate through an instance. With each iteration, the next element of the path is returned
 * - `Stringable`. Instances of the class can be safely passed to the functions that expect strings as a parameter
 * - `Equalable`. Allows comparing objects by calling `equals()` method
 */
class Path implements ArrayAccess, Countable, Iterator, Stringable, Equalable {

	/**
	 * Which separator to use when formatting a path. Allowed values are '\\' and '/', otherwise an exception is thrown.
	 * `DIRECTORY_SEPARATOR` by default.
	 * ```php
	 * Path::new('/a/b')->format([Path::OPTKEY_SEPARATOR => '\\']); // '\\a\\b'
	 * Path::new('/a/b')->format([Path::OPTKEY_SEPARATOR => ' ']);  // an exception
	 * ```
	 */
	public const OPTKEY_SEPARATOR = 'separator';

	/**
	 * Use trailing slash when formatting a path.
	 * `false` by default.
	 * ```php
	 * // An example
	 * Path::new('/a/b')->format([Path::OPTKEY_TRAILING_SLASH => true]);  // '/a/b/'
	 * Path::new('/a/b')->format([Path::OPTKEY_TRAILING_SLASH => false]); // '/a/b'
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
	 * Path::new('C:/Windows')->isDOS; // true
	 * Path::new('/root')->isDOS;      // false
	 * Path::new('file.txt')->isDOS;   // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isDOS;

	/**
	 * `true` if the path is Unix-like ('/var' and so). It's known only if it's an absolute path, so it's always false
	 * if the path is relative.
	 * ```php
	 * Path::new('C:/Windows')->isUnix; // false
	 * Path::new('/root')->isUnix;      // true
	 * Path::new('file.txt')->isUnix;   // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isUnix;

	/**
	 * `true` if the path is root. Root paths are a special case of absolute ones since they also start with a slash or
	 * a drive letter, but they denote only a root folder (like '/', 'C:\').
	 * ```php
	 * Path::new('C:\\')->isRoot;     // true
	 * Path::new('/')->isRoot;        // true
	 * Path::new('/usr/bin')->isRoot; // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isRoot;

	/**
	 * `true` if the path is absolute. Absolute paths are those that start with slashes ('/usr', '\\root') or a drive
	 * letter ('C:', 'C:/', 'C:\\'). It's the opposite of `isRelative`.
	 * ```php
	 * Path::new('C:\\Windows')->isAbsolute;         // true
	 * Path::new('/usr/bin')->isAbsolute;            // true
	 * Path::new('vendor/autoload.php')->isAbsolute; // false
	 * ```
	 * @var bool
	 */
	public readonly bool $isAbsolute;

	/**
	 * `true` if the path is relative. Relative paths are those that don't start with slashes or drive letter
	 * ('node_modules', 'public/index.php'). It's the opposite of `isAbsolute`.
	 * ```php
	 * Path::new('C:\\Windows')->isRelative;         // false
	 * Path::new('/usr/bin')->isRelative;            // false
	 * Path::new('vendor/autoload.php')->isRelative; // true
	 * ```
	 * @var bool
	 */
	public readonly bool $isRelative;

	/**
	 * Number of elements the path consists of. 0 is for root paths.
	 * ```php
	 * Path::new('/')->depth;                   // 0
	 * Path::new('C:\Users\Admin')->depth;      // 2
	 * Path::new('vendor/bin/phpunit')->depth;  // 3
	 * ```
	 * @var int
	 */
	public readonly int $depth;

	/**
	 * Contains drive letter if the path is DOS-like
	 * ```php
	 * Path::new('C:\\Windows')->drive; // 'C'
	 * Path::new('/var')->drive;        // null
	 * Path::new('vendor')->drive;      // null
	 * ```
	 * @var null|string
	 */
	public readonly ?string $drive;

	/**
	 * Normalized string representation of the path.
	 * ```php
	 * Path::new('/a/b\\c/')->path; // DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c'
	 * ```
	 * @var string
	 */
	public readonly string $path;

	private readonly array $data;
	private readonly int $dataSize;
	private int $i = 0;

	private function __construct(string $path) {
		$this->path = $path;
		$this->isDOS = self::isDOS($this->path);
		$this->isUnix = self::isUnix($this->path);
		$this->isRoot = !!preg_match(self::REGEX_ROOT, $this->path);
		$this->isAbsolute = $this->isDOS || $this->isUnix;
		$this->isRelative = !$this->isAbsolute;
		$data = self::split($path);
		if ($this->isRoot)
			array_pop($data);
		$this->data = $data;
		$this->dataSize = sizeof($data);
		$this->depth = $this->dataSize - ($this->isAbsolute ? 1 : 0);
		$this->drive = $this->isDOS ? $this->data[0][0] : null;
	}

	public function __toString(): string {
		return $this->path;
	}

	public function offsetExists(mixed $offset): bool {
		return is_int($offset) && $this->getElement($offset) !== null;
	}

	public function offsetGet(mixed $offset): mixed {
		if (is_int($offset))
			return $this->getElement($offset);
		trigger_error("Unable to get an element at index '" . addslashes(is_string($offset) ? $offset : '') . "' on path {$this}: using other types than integer are not allowed", E_USER_WARNING);
		return null;
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		throw new Exception("Unable to set the value '{$value}' at index {$offset}: instances of class " . self::class . ' are readonly');
	}
	
	public function offsetUnset(mixed $offset): void {
		throw new Exception("Unable to unset the value at index {$offset}: instances of class " . self::class . ' are readonly');
	}

	public function count(): int {
		return $this->depth;
	}

	public function current(): mixed {
		return $this->data[$this->i];
	}

	public function key(): mixed {
		return $this->i;
	}

	public function next(): void {
		$this->i++;
	}

	public function rewind(): void {
		$this->i = 0;
	}

	public function valid(): bool {
		return isset($this->data[$this->i]);
	}

	/**
	 * Check if the path is equal to another one. Paths are equal when their normalized versions match against each
	 * other.
	 * @param mixed $path Path to check against.
	 * @return bool `true` if both paths are equal.
	 */
	public function equals($path): bool {
		return $path !== null && ($path instanceof self || is_string($path)) && $this->path === self::normalize($path)->path;
	}

	/**
	 * Retrieve a part of the path by index. 0 is always for root elements, so retrieving an element at 0 when the path
	 * is relative always results in `null`. Negative indices start counting from the end while positive ones always
	 * start from the beginning.
	 * @param int $index Index to retrieve an element by.
	 * @return null|string Path element by the provided index or `null` if there is no such an element by the index.
	 * ```php
	 * Path::new('/')->getElement(0);              // ''
	 * Path::new('/var/www/html')->getElement(1);  // 'var'
	 * Path::new('/var/www/html')->getElement(-1); // 'html'
	 * Path::new('/var/www/html')->getElement(10); // null
	 * ```
	 */
	public function getElement(int $index): ?string {
		$realIndex = $this->getRealIndex($index);
		return isset($this->data[$realIndex]) ? $this->data[$realIndex] : null;
	}

	/**
	 * Get a parent of the path. If the path is root or a current directory then `null` is returned.
	 * @return null|Path Parent path or `null` if the path is root or a current directory.
	 * ```php
	 * Path::new('/usr/bin')->getParent(); // Path('/usr')
	 * Path::new('C:')->getParent();       // null
	 * Path::new('vendor')->getParent();   // null
	 * ```
	 */
	public function getParent(): ?self {
		if ($this->isRoot || $this->path === self::DIR_CURRENT)
			return null;
		if ($this->isRelative && $this->dataSize === 1)
			return self::new('.');
		return self::new(preg_replace('/[^\\\\\/]+$/', '', $this->path)); // TODO: Replace with subpath()
	}

	/**
	 * Get a subpath of the current path. Indices could be negative, in that case counting starts from the end.
	 * @param int $start Start index. 0 always denotes root paths.
	 * @param int $end End index.
	 * @return null|self Subpath of the current one or `null` of a subpath cannot be retrieved.
	 * ```php
	 * Path::new('/var/www/html')->getSubpath();      // Path('/var/www/html'); A copy of the current one
	 * Path::new('/var/www/html')->getSubpath(1);     // Path('var/www/html')
	 * Path::new('/var/www/html')->getSubpath(-1);    // Path('html')
	 * Path::new('/var/www/html')->getSubpath(2, -1); // Path('www/html')
	 * Path::new('/var/www/html')->getSubpath(10);    // null
	 * ```
	 */
	public function getSubpath(int $start = 0, int $end = -1): ?self {
		$realStart = !$start ? 0 : $this->getRealIndex($start);
		if ($realStart < 0)
			return null;
		$realEnd = $this->getRealIndex($end);
		if ($realEnd < 0)
			$realEnd = $this->dataSize;
		$data = $this->data;
		if ($this->isRoot)
			$data[] = '';
		return $realStart > $realEnd || $realStart > $this->dataSize ? null : self::new(
			join(
				self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR],
				array_slice(
					$data,
					$realStart,
					$realEnd - $realStart + 1
				)
			)
		);
	}

	/**
	 * Convert the path to an absolute one. It's a concatenation of `$base` and the path itself. If the path is already
	 * absolute then the path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path to make this one absolute against.
	 * @return Path An absolute normalized path.
	 * @throws InvalidArgumentException If the base is not an absolute path.
	 * ```php
	 * // An example
	 * Path::new('file.txt')->toAbsolute('C:\\Windows'); // Path('C:\\Windows\\file.txt')
	 * Path::new('/usr')->toAbsolute('/home');           // Path('/usr')
	 * Path::new('/usr')->toAbsolute('Windows');         // an exception
	 * ```
	 */
	public function toAbsolute(string | self $base): self {
		if ($this->isAbsolute)
			return clone $this;
		$base = self::wrap($base);
		if (!$base->isAbsolute)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to absolute: the base '{$base->path}' is not absolute");
		return self::join($base->path, $this->path);
	}

	/**
	 * Convert the path to a relative one. It rips the `$base` component out of the path. If the path is already
	 * relative then the path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path that will be ripped out of the path.
	 * @return Path A relative normalized path.
	 * @throws InvalidArgumentException If the base is not an absolute path.
	 * ```php
	 * // An example
	 * Path::new('C:\\Windows\\file.txt')->toRelative('C:/Windows'); // Path('file.txt')
	 * Path::new('file.txt')->toRelative('C:/Windows');              // Path('file.txt')
	 * Path::new('file.txt')->toRelative('config.json');             // an exception
	 * ```
	 */
	public function toRelative(string | self $base): self {
		if ($this->isRelative)
			return clone $this;
		$base = self::wrap($base);
		if (!$base->isAbsolute)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to relative: the base '{$base->path}' is not absolute");
		if (strpos($this->path, $base->path) !== 0)
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to relative: the base '{$base->path}' is not a parent of the path");
		$result = substr($this->path, strlen($base->path));
		$result = ltrim($result, '\\/');
		return self::new($result);
	}

	/**
	 * Format the path and return a string representation of it. The separator is `DIRECTORY_SEPARATOR` and
	 * 'trailingSlash' is false by default.
	 * @param array $options Options to use. See `OPTKEY_*` public constants for the documentation.
	 * @return string Formatted string.
	 * @throws InvalidArgumentException If the separator is not a slash.
	 * ```php
	 * Path::new('c:/windows\\file.txt')->format(['separator' => '\\', 'trailingSlash' => false]); // 'C:\\Windows\\file.txt'
	 * Path::new('\\usr\\bin')->format(['separator' => '/', 'trailingSlash' => true]);             // '/usr/bin/'
	 * ```
	 */
	public function format(array $options = self::DEFAULT_OPTIONS): string {
		$options = array_merge(self::DEFAULT_OPTIONS, $options);
		self::checkOptions($options);
		return preg_replace(self::REGEX_SLASH, $options[self::OPTKEY_SEPARATOR], $this->path . ($options[self::OPTKEY_TRAILING_SLASH] ? $options[self::OPTKEY_SEPARATOR] : ''));
	}

	/**
	 * Check if the current path starts with the given one. It's not the same as `str_starts_with()`, because this
	 * method checks elements of paths and not symbols.
	 * @param string|self $path Path to check against.
	 * @return bool `true` if the current path starts with the given one.
	 * ```php
	 * Path::new('/var/www/html')->startsWith('/');        // true
	 * Path::new('/var/www/html')->startsWith('/var/www'); // true
	 * Path::new('/var/www/html')->startsWith('/var/w');   // false
	 * ```
	 */
	public function startsWith(string | self $path): bool {
		$path = self::wrap($path);
		foreach ($path->data as $i => $element)
			if ($this->data[$i] !== $element)
				return false;
		return true;
	}

	/**
	 * Check if the current path ends with the given one. It's not the same as `str_ends_with()`, because this method
	 * checks elements of paths and not symbols.
	 * @param string|self $path Path to check against.
	 * @return bool `true` if the current path ends with the given one.
	 * ```php
	 * Path::new('/var/www/html')->endsWith('html');     // true
	 * Path::new('/var/www/html')->endsWith('www/html'); // true
	 * Path::new('/var/www/html')->endsWith('ww/html');  // false
	 * ```
	 */
	public function endsWith(string | self $path): bool {
		$path = self::wrap($path);
		for ($i = $path->dataSize - 1, $j = $this->dataSize - 1; $i >= 0; $i--, $j--)
			if ($path->data[$i] !== $this->data[$j])
				return false;
		return true;
	}

	/**
	 * Check if the current path is a direct child of the given one.
	 * @param string|self $path Path to check against.
	 * @return bool `true` if the current path is a direct child of the given one.
	 * ```php
	 * Path::new('/var/www/html')->isChildOf('/var/www');        // true
	 * Path::new('vendor/bin/phpunit')->isChildOf('vendor/bin'); // true
	 * Path::new('/var/www/html')->isChildOf('/var');            // false
	 * Path::new('vendor/bin/phpunit')->isChildOf('vendor/b');   // false
	 * ```
	 */
	public function isChildOf(string | self $path): bool {
		$thisParent = $this->getParent();
		return $thisParent !== null && $thisParent->equals($path);
	}

	/**
	 * Check if the current path is a direct parent of the given one.
	 * @param string|self $path Path to check against.
	 * @return bool `true` if the current path is a direct parent of the given one.
	 * ```php
	 * Path::new('/var/www')->isParentOf('/var/www/html');        // true
	 * Path::new('vendor/bin')->isParentOf('vendor/bin/phpunit'); // true
	 * Path::new('/var')->isParentOf('/var/www/html');            // false
	 * Path::new('vendor/b')->isParentOf('vendor/bin/phpunit');   // true
	 * ```
	 */
	public function isParentOf(string | self $path): bool {
		$pathParent = self::wrap($path)->getParent();
		return $pathParent !== null && $pathParent->equals($this);
	}

	/**
	 * Check if the current path contains the given subpath.
	 * @param string|self $path Subpath to check against.
	 * @return bool `true` if the current path contains the given one as a subpath.
	 * ```php
	 * Path::new('/var/www/html')->includes('www');      // true
	 * Path::new('/var/www/html')->includes('www/html'); // true
	 * Path::new('/var/www/html')->includes('/www');     // false
	 * ```
	 */
	public function includes(string | self $path): bool {
		return $this->firstIndexOf($path) >= 0;
	}

	/**
	 * Retrieve the first index of occurence of the given path. 0 is for root.
	 * @param string|self $path Subpath to find the first index of.
	 * @param int $start Index to start searching with.
	 * @return int Index of the first occurence of the given subpath or `-1` if the subpath is not found.
	 * ```php
	 * Path::new('/var/www/html')->firstIndexOf('/');        // 0
	 * Path::new('/var/www/html')->firstIndexOf('/var');     // 0
	 * Path::new('/var/www/html')->firstIndexOf('var');      // 1
	 * Path::new('/var/www/html')->firstIndexOf('www');      // 2
	 * Path::new('/var/www/html')->firstIndexOf('www/html'); // 2
	 * Path::new('/var/www/html')->firstIndexOf('/www');     // -1
	 * ```
	 */
	public function firstIndexOf(string | self $path, int $start = 0): int {
		$path = self::wrap($path);
		if (!$start && !$this->isAbsolute)
			$start = 1;
		if ($start)
			$start = $this->getRealIndex($start);
		for ($i = $start; $i < $this->dataSize; $i++) {
			for ($j = 0; $j < $path->dataSize; $j++)
				if (@$this->data[$j + $i] !== @$path->data[$j])
					continue 2;
			return $i + +!$this->isAbsolute;
		}
		return -1;
	}

	/**
	 * Retrieve the last index of occurence of the given path. 0 is for root.
	 * @param string|self $path Subpath to find the last index of.
	 * @param int $end Index to start searching with.
	 * @return int Index of the last occurence of the given subpath or `-1` if the subpath is not found.
	 * ```php
	 * Path::new('/a/b/c/a/b/c')->lastIndexOf('a/b'); // 4
	 * ```
	 */
	public function lastIndexOf(string | self $path, int $end = -1): int {
		$path = self::wrap($path);
		if ($end === -1)
			$end = $this->dataSize - 1;
		elseif (!$end && !$this->isAbsolute)
			$end = 0;
		else
			$end = $this->getRealIndex($end);
		for ($i = $end; $i >= 0; $i--) {
			for ($j = 0; $j < $path->dataSize; $j++) {
				if (@$this->data[$i + $j] !== @$path->data[$j])
					continue 2;
			}
			return $i + +!$this->isAbsolute;
		}
		return -1;
	}

	/**
	 * Concatenate the given paths (`DIRECTORY_SEPARATOR` is used as a separator) and normalize the result.
	 * @param string[] $data Paths to concatenate
	 * @return Path Concatenated path.
	 * @uses \Stein197\Path::normalize()
	 * ```php
	 * Path::join(__DIR__, 'vendor/bin', 'phpunit.bat'); // Path('/usr/www/vendor/bin/phpunit.bat')
	 * Path::join('../..', 'phpunit.bat');               // Path('../../phpunit.bat')
	 * ```
	 */
	public static function join(string ...$data): self {
		return self::normalize(join(self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR], $data));
	}

	/**
	 * Normalize a path and expand environment variables like '%SystemRoot%' for Windows and '$HOME' for Unix.
	 * Windows-like variables enclosed within percent characters are considered as case-insensitive, while for Unix-like 
	 * ones considered as case-sensitive.
	 * @param string $path Path to expand variables within.
	 * @param array $env Override environment variables.
	 * @return Path Path with expanded variables.
	 * ```php
	 * // An example
	 * Path::expand('%SystemRoom%\\Downloads');                 // Path('C:\\Users\\Admin\\Downloads')
	 * Path::expand('$HOME\\bin');                              // Path('/home/admin/bin')
	 * Path::expand('$varname/admin', ['varname' => '/home/']); // Path(/home/admin')
	 * ```
	 */
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

	/**
	 * Create a new path instance. Alias for `Path::normalize()`.
	 * @param string|self $path String to create a path from.
	 * @return self Instantiated path.
	 */
	public static function new(string | self $path): self {
		return self::normalize($path);
	}

	/**
	 * Find a common longest path among the given paths.
	 * @param (string|self)[] $paths Paths to find a base among.
	 * @return null|self Base path or `null` if there is no base path.
	 * ```php
	 * Path::findCommonBase('/var/www/html', '/var/www/css', '/var/www/js'); // Path('/var/www')
	 * Path::findCommonBase('/var/www/html', 'C:\\Windows');                 // null
	 * ```
	 */
	public static function findCommonBase(string | self ...$paths): ?self {
		if (!$paths)
			return null;
		$paths = array_map(fn (string | self $path): self => self::wrap($path), $paths);
		$result = [...$paths[0]];
		foreach ($paths as $path) {
			foreach ($path as $i => $part)
				if (!isset($result[$i]) || $result[$i] !== $part) {
					array_splice($result, $i);
					break;
				}
			if (!$result)
				return null;
		}
		$result = join(self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR], $result);
		if (preg_match(self::REGEX_ROOT, $result))
			$result .= self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR];
		return self::new($result);
	}

	private function getRealIndex(int $index): int {
		return match (true) {
			!$index => $this->isAbsolute ? $index : -1,
			$index > 0 => $index - +!$this->isAbsolute,
			$index < 0 => $this->isAbsolute && abs($index) >= $this->dataSize ? -1 : $this->dataSize + $index,
			default => $index
		};
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
							fn (string $char): string => "'{$char}'",
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

	private static function wrap(string | self $path): self {
		return $path instanceof self ? $path : self::new($path);
	}
}

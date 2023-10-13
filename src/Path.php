<?php
namespace Stein197;

use Exception;
use InvalidArgumentException;
use Stringable;
use function array_map;
use function array_merge;
use function array_pop;
use function array_search;
use function getenv;
use function join;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
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
 * 'vendor/autoload.php' etc.). The class contains functions that help normalize, resolve (concatenate),
 * format paths and so on.
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
 * although it can be changed when passing an options object down to {@link \Stein197\Path::format()} function.
 * 
 * The class also implements `Stringable` interface, so the instance of this class can be safely passed to functions
 * that expect strings as a parameter.
 */
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
	private const REGEX_PATH_ABSOLUTE_WIN = '/^(?:[a-z]:)[\\\\\/]?/i';
	private const REGEX_PATH_ABSOLUTE_NIX = '/^[\\\\\/]/';
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
	 * Raw path string that was passed to the constructor. It differs from what other methods could return.
	 * ```php
	 * // An example
	 * $p = new Path('/a/b\\c/');
	 * $p->format(); // could be '/a/b/c'
	 * $p->raw;      // always '/a/b\\c/'
	 * ```
	 * @var string
	 */
	public readonly string $raw;

	private string $path;

	/**
	 * Create a new path object.
	 * @param string|self $data A string or another path object.
	 * @throws InvalidArgumentException When the string is empty.
	 */
	public function __construct(string | self $data) {
		$this->path = $this->raw = strval($data);
		if (!$this->raw)
			throw new InvalidArgumentException('Cannot instantiate a path object: the path string is empty');
	}

	public function __toString(): string {
		return $this->format(self::DEFAULT_OPTIONS);
	}

	public function equals($path): bool {
		return $path instanceof self && self::normalize($this)->path === self::normalize($path)->path;
	}

	/**
	 * Check if the path is absolute. Absolute paths are those that start with slashes ('/usr', '\\root') or a drive
	 * letter ('C:', 'C:/', 'C:\\'). It's the opposite of {@link \Stein197\Path::isRelative()}
	 * @return bool `true` if the path is absolute.
	 * ```php
	 * // An example
	 * (new Path('C:\\Windows'))->isAbsolute();         // true
	 * (new Path('/usr/bin'))->isAbsolute();            // true
	 * (new Path('vendor/autoload.php'))->isAbsolute(); // false
	 * ```
	 */
	public function isAbsolute(): bool {
		return $this->getType() != null;
	}

	/**
	 * Check if the path is relative. Relative paths are those that don't start with slashes or drive letter
	 * ('node_modules', 'public/index.php'). It's the opposite of {@link \Stein197\Path::isAbsolute()}
	 * @return bool `true` if the path is relative.
	 * ```php
	 * // An example
	 * (new Path('C:\\Windows'))->isRelative();         // false
	 * (new Path('/usr/bin'))->isRelative();            // false
	 * (new Path('vendor/autoload.php'))->isRelative(); // true
	 * ```
	 */
	public function isRelative(): bool {
		return !$this->isAbsolute();
	}

	/**
	 * Check if the path is root. Root paths are a special case of absolute ones since they also start with a slash or
	 * a drive letter, but they denote only a root folder (like '/', 'C:\').
	 * @return bool `true` if the path is root.
	 * ```php
	 * // An example
	 * (new Path('C:\\'))->isRoot();     // true
	 * (new Path('/'))->isRoot();        // true
	 * (new Path('/usr/bin'))->isRoot(); // false
	 * ```
	 */
	public function isRoot(): bool {
		return !!preg_match(self::REGEX_ROOT, $this->path);
	}

	/**
	 * Get parent of the path. If the path is root then `null` is returned.
	 * @return null|Path Parent path or `null` if the path is root.
	 * ```php
	 * // An example
	 * (new Path('/usr/bin'))->getParent(); // Path('/usr')
	 * (new Path('C:'))->getParent();       // null
	 * ```
	 */
	public function getParent(): ?self {
		if ($this->isRoot())
			return null;
		try {
			return new self(preg_replace('/[^\\\\\/]+(?:[\\\\\/])?$/', '', $this->path));
		} catch (Exception) {
			return null;
		}
	}

	/**
	 * Get type of a path. It can be Windows, Unix or none. Type of the path is known only if it's an absolute path.
	 * @return null|PathType Path type or `null` if the path is relative.
	 * ```php
	 * // An example
	 * (new Path('C:/Windows'))->getType(); // PathType::Windows
	 * (new Path('/root'))->getType();      // PathType::Unix
	 * (new Path('file.txt'))->getType();   // null
	 * ```
	 */
	public function getType(): ?PathType {
		$isWinAbsolute = !!preg_match(self::REGEX_PATH_ABSOLUTE_WIN, $this->path);
		$isNixAbsolute = !!preg_match(self::REGEX_PATH_ABSOLUTE_NIX, $this->path);
		return match (true) {
			$isWinAbsolute => PathType::Windows,
			$isNixAbsolute => PathType::Unix,
			default => null
		};
	}

	/**
	 * Convert the path to an absolute one. It's a concatenation of `$base` and the path itself. If the path is already
	 * absolute then the path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path to make this one absolute against.
	 * @return Path An absolute path.
	 * @throws InvalidArgumentException If the base is not an absolute path.
	 * ```php
	 * // An example
	 * (new Path('file.txt'))->toAbsolute('C:\\Windows'); // Path('C:\\Windows\\file.txt')
	 * (new Path('/usr'))->toAbsolute('/home');           // Path('/usr')
	 * (new Path('/usr'))->toAbsolute('Windows');         // an exception
	 * ```
	 */
	public function toAbsolute(string | self $base): self {
		if ($this->isAbsolute())
			return $this;
		$base = $base instanceof self ? $base : new self($base);
		if (!$base->isAbsolute())
			throw new InvalidArgumentException("Cannot convert the path '{$this->path}' to absolute: the base '{$base->path}' is not absolute");
		return self::resolve($base, $this);
	}

	/**
	 * Convert the path to a relative one. It rips the `$base` component out of the path. If the path is already
	 * relative thethe path itself is returned. If the base is not an absolute path, then an exception is thrown.
	 * @param string|Path $base Path that will be ripped out of the path.
	 * @return Path A relative path.
	 * @throws InvalidArgumentException If the base is not a relative path.
	 * ```php
	 * // An example
	 * (new Path('C:\\Windows\\file.txt'))->toRelative('C:/Windows'); // Path('file.txt')
	 * (new Path('file.txt'))->toRelative('C:/Windows');              // Path('file.txt')
	 * (new Path('file.txt'))->toRelative('config.json');             // an exception
	 * ```
	 */
	public function toRelative(string | self $base): self {
		if ($this->isRelative())
			return $this;
		$base = $base instanceof self ? $base : new self($base);
		if (!$base->isAbsolute())
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
	 * @return Path Resolved path.
	 * @throws InvalidArgumentException If there are too many parent jumps.
	 * @uses \Stein197\Path::normalize()
	 * ```php
	 * // An example
	 * Path::resolve(__DIR__, 'vendor/bin', 'phpunit.bat'); // Path('/usr/www/vendor/bin/phpunit.bat')
	 * Path::resolve('../..', 'phpunit.bat');               // an exception
	 * ```
	 */
	public static function resolve(string ...$data): self {
		return self::normalize(join(self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR], $data));
	}

	/**
	 * Normalize a path and expand environment variables like '%SystemRoot%' for Windows and '$HOME', '~' for Unix.
	 * Windows-like variables enclosed within percent characters are considered as case-insensetive, while for Unix-like 
	 * ones considered as case-sensetive.
	 * @param string $path Path to expand variables within.
	 * @param array $env Override environment variables.
	 * @return Path Path with expanded variables.
	 * ```php
	 * // An example
	 * Path::expand('%SystemRoom%\\Downloads'); // Path('C:\\Users\\Admin\\Downloads')
	 * Path::expand('$HOME\\bin');              // Path('/home/admin/bin')
	 * Path::expand('~/downloads');             // Path('/home/admin/downloads')
	 * ```
	 */
	public static function expand(string | self $path, ?array $env = null): self {
		return self::normalize(preg_replace_callback(self::REGEX_ENV_VAR, function (array $matches) use ($env): string {
			[$match] = $matches;
			$type = str_starts_with($match, '$') ? PathType::Unix : PathType::Windows;
			$name = trim($match, '%$');
			switch ($type) {
				case PathType::Windows:
					$name = strtolower($name);
					if ($env)
						foreach ($env as $varName => $value)
							if ($name === strtolower($varName))
								return $value;
					break;
				case PathType::Unix:
					if ($env && isset($env[$name]))
						return $env[$name];
					break;
			}
			return getenv($name, true) ?: getenv($name, false) ?: '';
		}, $path));
	}

	/**
	 * Normalize the given path. The next operations will be performed:
	 * - Removing redundant slashes ('C:\\\\Windows\\file.txt' -> 'C:\\Windows\\file.txt')
	 * - Removing current directory path ('./vendor' -> 'vendor')
	 * - Removing parent jumps ('vendor/../public' -> 'public')
	 * - Unification of slashes (`DIRECTORY_SEPARATOR` is used, '\\usr/bin' -> '/usr/bin')
	 * - Trimming of trailing slashes ('/usr/bin/' -> '/usr/bin')
	 * - Capitalizing drive letters ('c:/windows' -> 'C:/windows')
	 * 
	 * Note, that PHP's `realpath()` returns `false` if the path doesn't exist. `Path::normalize()` doesn't rely on a
	 * path existence, so an object is always returned.
	 * @param string $path Path to normalize.
	 * @return Path Normalized path.
	 * @throws InvalidArgumentException If there were too many parent jumps
	 * ```php
	 * // An example
	 * Path::normalize('//usr\\bin/./..\\www\\'); // Path('/usr/www')
	 * Path::normalize('C:\\Windows\\..\\..');    // an exception
	 * ```
	 */
	// TODO: Uppercase drive letter when it's a windows path
	public static function normalize(string $path): self {
		$result = [];
		$parts = self::split($path);
		foreach ($parts as $part) {
			if ($part === self::DIR_CURRENT) {
				continue;
			} elseif ($part === self::DIR_PARENT) {
				$isOut = !$result || sizeof($result) === 1 && (!!preg_match(self::REGEX_ROOT, $result[0]) || !$result[0]);
				if ($isOut)
					throw new InvalidArgumentException("Cannot normalise the path '{$path}': too many parent jumps");
				array_pop($result);
			} else {
				$result[] = $part;
			}
		}
		if (preg_match(self::REGEX_PATH_ABSOLUTE_WIN, $result[0]))
			$result[0] = strtoupper($result[0]);
		$result = join(self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR], $result);
		if (!preg_match(self::REGEX_ROOT, $result))
			$result = rtrim($result, self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR]);
		if (preg_match(self::REGEX_ROOT, $result) && preg_match(self::REGEX_PATH_ABSOLUTE_WIN, $result))
			$result .= self::DEFAULT_OPTIONS[self::OPTKEY_SEPARATOR];
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
}

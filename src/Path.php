<?php

namespace Stein197;

use Exception;
use InvalidArgumentException;
use function array_merge;
use function array_pop;
use function join;
use function strpos;
use function sprintf;
use function sizeof;
use function substr;
use function strlen;
use function preg_match;
use function preg_split;
use function ltrim;
use function rtrim;
use const DIRECTORY_SEPARATOR;

/**
 * Wrapper around paths that unifies the management of strings like `/a/b/c` or `C:\Windows` and so on. All paths fall
 * into three categories:
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
 * (`/`, `C:\`, `D:`). All methods that perform actions on paths return a new object instead of modifying the current
 * one. The returned object satisfies the following conditions:
 * - The path is delimited by only one separator and it's a system separator (`DIRECTORY_SEPARATOR` const)
 * - The path does not contain the trailing slash at the end (the root path is the only exception)
 * - If it's a resolving operation then the different paths are just being concatenated by a system separator
 * 
 * Those conditions could be changed. To do so, many methods accept an optional parameter $options that is an array:
 * ```php
 * $options = [
 *     'separator'     => DIRECTORY_SEPARATOR, // Separator to use ('\' or '/')
 *     'preserveSlash' => false,               // If true AND the path ends with slash, preserve the trailing slash, discard otherwise
 *     'trailingSlash' => false,               // If true then force the resulting path to have trailing slash even if it does not have one
 *     'baseResolve'   => false                // If true AND paths look like ['/base', 'child'], then the result is '/child', '/base/child' otherwise. Useful when working with HTML links
 * ];
 * ```
 */
class Path {

	public const OPTKEY_SEPARATOR = 'separator';
	public const OPTKEY_PRESERVE_SLASH = 'preserveSlash';
	public const OPTKEY_TRAILING_SLASH = 'trailingSlash';
	public const OPTKEY_BASE_RESOLVE = 'baseResolve';
	private const MSG_TOO_MANY_JUMPS     = 'Cannot resolve path "%s": too many parent jumps';
	private const MSG_EMPTY_STRING       = 'Cannot instantiate an object: the string is empty';
	private const MSG_INVALID_SEPARATOR  = 'Cannot use separator "%s": only "\\" and "/" are allowed';
	private const MSG_RELATIVE_PATH      = 'Cannot make the path "%s" %s: "%s" is relative';
	private const DEFAULT_OPTIONS = [
		self::OPTKEY_SEPARATOR      => DIRECTORY_SEPARATOR,
		self::OPTKEY_PRESERVE_SLASH => false,
		self::OPTKEY_TRAILING_SLASH => false,
		self::OPTKEY_BASE_RESOLVE   => false
	];

	private string $path;

	/**
	 * Creates a new path.
	 * @param string|self $path Path to create from.
	 * @throws InvalidArgumentException If the string is empty.
	 */
	public function __construct(string | self $path) {
		$this->path = $path instanceof self ? $path->path : $path;
		if (!$this->path)
			throw new InvalidArgumentException(self::MSG_EMPTY_STRING);
	}

	public function __toString(): string {
		return $this->path;
	}

	/**
	 * Returns the parent of the path.
	 * @param bool $preserveSlash Preserve the trailing slash of the parent path.
	 * @return null|self Parent of the path or `null` if there's no a parent (root path).
	 */
	public function getParent(bool $preserveSlash = false): ?self {
		if ($this->isRoot())
			return null;
		try {
			$parent = new self(preg_replace('/[^\\\\\/]+(?:[\\\\\/])?$/', '', $this->path));
		} catch (InvalidArgumentException) {
			return null;
		}
		if (!$preserveSlash && !$parent->isRoot())
			$parent->path = preg_replace('/[\\\\\/]+$/', '', $parent->path);
		return $parent;
	}

	/**
	 * Makes the current path absolute and resolves it to the given one. For example if the current path is 'c/d' and
	 * the given one is '/a/b' then the result would be '/a/b/c/d'. Does nothing if the current path is absolute.
	 * @param string|self $base A path against which the current one is being absolutized.
	 * @param array $options Array of options, see {@see \Stein197\Path::DEFAULT_OPTIONS the default options}
	 * @return self Resolved absolute path.
	 * @throws InvalidArgumentException If the given base path is relative.
	 * @throws Exception If there're too many parent jumps.
	 * @see \Stein197\Path::resolve()
	 */
	public function toAbsolute(string | self $base, array $options = self::DEFAULT_OPTIONS): self {
		$options = self::mergeOptions($options);
		if ($this->isAbsolute())
			return $this->format($options);
		$base = $base instanceof self ? $base : new self($base);
		if (!$base->isAbsolute())
			throw new InvalidArgumentException(sprintf(self::MSG_RELATIVE_PATH, $this->path, 'absolute', $base->path));
		return (new self(self::resolve([$base->path, $this->path], $options)));
	}

	/**
	 * Makes the current path relative by cutting the absolute part of $base. For example if the current path is
	 * '/a/b/c/d' and the base is '/a/b' then the result would be 'c/d'. Does nothing if the current path is relative.
	 * @param string|self $base A path against which the current one is being relativized.
	 * @param array $options Array of options, see {@see \Stein197\Path::DEFAULT_OPTIONS the default options}
	 * @return ?self Resolved relative path. Returns `null` if the current path does not overlap with the base one.
	 * @throws InvalidArgumentException If the given base path is relative.
	 * @throws Exception If there're too many parent jumps.
	 */
	public function toRelative(string | self $base, array $options = self::DEFAULT_OPTIONS): ?self {
		$options = self::mergeOptions($options);
		if ($this->isRelative())
			return $this->format($options);
		$base = ($base instanceof self ? $base : new self($base))->format($options);
		if (!$base->isAbsolute())
			throw new InvalidArgumentException(sprintf(self::MSG_RELATIVE_PATH, $this->path, 'relative', $base->path));
		$path = $this->format($options);
		if (strpos($path, $base) === 0)
			return new self(ltrim(substr($path, strlen($base)), '\\/'));
		return null;
	}

	/**
	 * Checks if the path is absolute.
	 * @return bool `true` if the path is absolute.
	 */
	public function isAbsolute(): bool {
		[$first] = self::split($this->path);
		return !$first || preg_match('/^[a-z]:$/i', $first);
	}

	/**
	 * Checks if the path is relative. It's an opposite of `self::isAbsolute()`.
	 * @return bool `true` if the path is relative.
	 * @uses \Stein197\Path::isAbsolute()
	 */
	public function isRelative(): bool {
		return !$this->isAbsolute();
	}

	/**
	 * Checks if the path is root.
	 * @return bool `true` if it's a root.
	 */
	public function isRoot(): bool {
		return !!preg_match('/^(?:[a-z]:)?(?:[\\\\\/])?$/i', $this->path);
	}

	private function format(array $options): self {
		$options = self::mergeOptions($options);
		$path = $this->path;
		$path = join($options[self::OPTKEY_SEPARATOR], self::split($this->path));
		$path = $path === '/' || $path === '\\' ? $path : rtrim($path, '/\\');
		$path .= self::shouldAppendSlash($this->path, $options) ? $options[self::OPTKEY_SEPARATOR] : '';
		return new self($path);
	}

	/**
	 * Joins different paths into a single one and normalizes it.
	 * @param string[] $paths Paths to join.
	 * @param array $options Options to use.
	 * @return string Resolved path.
	 * @throws Exception If there are too many parent jumps.
	 * @uses \Stein197\Path::normalize()
	 */
	public static function resolve(array $paths, array $options = self::DEFAULT_OPTIONS): string {
		$options = self::mergeOptions($options);
		$result = '';
		foreach ($paths as $path) {
			if (!$path)
				continue;
			$isBase = $options[self::OPTKEY_BASE_RESOLVE] && !self::endsWithSlash($result) && !preg_match('/^[a-z]:$/i', $result);
			if ($isBase)
				$result = preg_replace('/[^\\\\\/]+$/', '', $result);
			$result .= ($result ? $options[self::OPTKEY_SEPARATOR] : '').$path;
		}
		return self::normalize($result, $options);
	}

	/**
	 * Normalizes the given path. Removes redundant separators, path jumps ("." and "..").
	 * @param string $path Path to normalize.
	 * @param array $options Options to use.
	 * @return string Normalized path.
	 * @throws Exception If the path contains too many parent jumps.
	 * @see \Stein197\Path::DEFAULT_OPTIONS
	 */
	public static function normalize(string $path, array $options = self::DEFAULT_OPTIONS): string {
		$result = [];
		$options = self::mergeOptions($options);
		foreach (self::split($path) as $part) {
			if ($part === '.') {
				continue;
			} elseif ($part === '..') {
				$isOut = !$result || sizeof($result) === 1 && (self::isDrive($result[0]) || !$result[0]);
				if ($isOut)
					throw new Exception(sprintf(self::MSG_TOO_MANY_JUMPS, $path));
				array_pop($result);
			} else {
				$result[] = $part;
			}
		}
		$result = join($options['separator'], $result);
		$result = $result == '/' || $result == '\\' ? $result : rtrim($result, '\\/');
		return $result.(self::shouldAppendSlash($path, $options) || self::shouldAppendSlash($result, $options) ? $options[self::OPTKEY_SEPARATOR] : '');
	}

	private static function split(string $path): array {
		return preg_split('/[\\\\\/]+/', $path);
	}

	/** @throws InvalidArgumentException */
	private static function mergeOptions(array $options): array {
		if (@$options[self::OPTKEY_SEPARATOR] && @$options[self::OPTKEY_SEPARATOR] !== '\\' && @$options[self::OPTKEY_SEPARATOR] !== '/')
			throw new InvalidArgumentException(sprintf(self::MSG_INVALID_SEPARATOR, $options[self::OPTKEY_SEPARATOR]));
		return array_merge(self::DEFAULT_OPTIONS, $options);
	}

	private static function endsWithSlash(string $path): bool {
		return !!preg_match('/[\\\\\/]$/', $path);
	}

	private static function shouldAppendSlash(string $path, array $options): bool {
		return @$options[self::OPTKEY_TRAILING_SLASH] || @$options[self::OPTKEY_PRESERVE_SLASH] && self::endsWithSlash($path) || self::isDrive($path);
	}

	private static function isDrive(string $path): bool {
		return preg_match('/^[a-z]:$/i', $path);
	}
}

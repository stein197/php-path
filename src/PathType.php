<?php
namespace Stein197;

/**
 * Represent different path types depending on the operating system.
 */
enum PathType {

	/**
	 * Windows-like path (like 'C:\\Windows', 'C:/', 'C:') that starts with drive letter.
	 */
	case Windows;

	/**
	 * Unix-like path (like '/usr', '\\', '/') that starts with a slash.
	 */
	case Unix;
}

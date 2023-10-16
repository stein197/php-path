<?php
namespace Stein197\FileSystem;

/**
 * Represent different path types depending on the operating system.
 */
enum PathType {

	/**
	 * DOS-like path (like 'C:\\Windows', 'C:/', 'C:') that starts with drive letter.
	 */
	case DOS;

	/**
	 * Unix-like path (like '/usr', '\\', '/') that starts with a slash.
	 */
	case Unix;
}

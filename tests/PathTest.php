<?php

namespace Stein197;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase {

	private const ROOT_PATHS = [
		'/', '\\', 'C:', 'C:\\', 'C:/'
	];

	public function test___construct_should_throw_an_exception_when_the_argument_is_empty(): void {
		$this->expectException(InvalidArgumentException::class);
		new Path('');
	}

	public function test___toString_should_return_the_original_string(): void {
		$this->assertEquals('/var/www', (string) new Path('/var/www'));
	}

	public function test_getParent_should_return_null_when_the_path_is_root(): void {
		foreach (self::ROOT_PATHS as $root)
			$this->assertNull((new Path($root))->getParent());
	}

	public function test_getParent_should_return_null_when_the_path_is_relative_and_single(): void {
		$this->assertNull((new Path('a'))->getParent());
	}

	public function test_getParent_should_return_root_when_the_path_is_absolute_and_single(): void {
		$cases = [
			['/', '/a'],
			['\\', '\\a'],
			['C:\\', 'C:\\a'],
			['C:/', 'C:/a'],
		];
		foreach ($cases as [$expected, $path])
			$this->assertEquals($expected, (string) (new Path($path))->getParent());
	}

	public function test_getParent_should_return_result_with_the_same_format_as_the_current_path(): void {
		$this->assertEquals('/a/b\\c', (string) (new Path('/a/b\\c\\d'))->getParent());
	}

	public function test_getParent_should_return_correct_result_when_the_path_is_relative(): void {
		$this->assertEquals('a', (string) (new Path('a\\b'))->getParent());
		$this->assertEquals('a/b', (string) (new Path('a/b/c'))->getParent());
	}

	public function test_getParent_should_return_correct_result_when_the_path_is_absolute(): void {
		$this->assertEquals('C:\\a', (string) (new Path('C:\\a\\b'))->getParent());
		$this->assertEquals('/a/b', (string) (new Path('/a/b/c'))->getParent());
	}

	public function test_getParent_should_return_correct_result_without_trailing_slash_when_the_argument_is_default(): void {
		$this->assertEquals('/a/b', (string) (new Path('/a/b/c'))->getParent());
	}

	public function test_getParent_should_return_correct_result_without_trailing_slash_when_the_argument_is_false(): void {
		$this->assertEquals('/a/b', (string) (new Path('/a/b/c'))->getParent(false));
	}

	public function test_getParent_should_return_correct_result_with_trailing_slash_when_the_arguent_is_true(): void {
		$this->assertEquals('/a/b/', (string) (new Path('/a/b/c'))->getParent(true));
	}
	
	public function test_getParent_should_return_null_when_the_path_is_drive(): void {
		$this->assertNull((new Path('C:\\'))->getParent());
	}

	public function test_toAbsolute_should_throw_an_exception_when_the_base_path_is_relative(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot make the path "b" absolute: "a" is relative');
		(new Path('b'))->toAbsolute('a');
	}

	public function test_toAbsolute_should_throw_an_exception_when_the_separator_is_invalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot use separator " ": only "\\" and "/" are allowed');
		(new Path('b'))->toAbsolute('/a', ['separator' => ' ']);
	}

	public function test_toAbsolute_should_return_the_same_path_when_it_is_absolute(): void {
		$this->assertEquals('/b', (string) (new Path('/b'))->toAbsolute('/a', ['separator' => '/']));
	}

	public function test_toAbsolute_should_return_correct_result_when_the_base_is_root(): void {
		$cases = [
			['/', '/a', 'a'],
			['\\', '/a', 'a'],
			['C:', 'C:/a', 'a'],
			['C:\\', 'C:/a', 'a'],
			['C:/', 'C:/a', 'a'],
		];
		foreach ($cases as [$root, $expected, $path])
			$this->assertEquals($expected, (string) (new Path($path))->toAbsolute($root, ['separator' => '/']));
	}

	public function test_toAbsolute_should_return_correct_result_when_the_separator_is_specified(): void {
		$this->assertEquals('/a/b/c', (string) (new Path('b/c'))->toAbsolute('/a', ['separator' => '/']));
	}

	public function test_toAbsolute_should_return_correct_result_without_a_slash_at_the_end_when_there_is_no_a_slash_and_preserveSlash_is_true(): void {
		$this->assertEquals('/a/b/c', (string) (new Path('b/c'))->toAbsolute('/a', ['separator' => '/', 'preserveSlash' => true]));
		$this->assertEquals('C:\\a\\b\\c\\d', (string) (new Path('c\\d'))->toAbsolute('C:\\a\\b', ['separator' => '\\', 'preserveSlash' => true]));
	}

	public function test_toAbsolute_should_return_correct_result_with_a_slash_at_the_end_when_there_is_a_slash_and_preserveSlash_is_true(): void {
		$this->assertEquals('/a/b/c/', (string) (new Path('b/c/'))->toAbsolute('/a', ['separator' => '/', 'preserveSlash' => true]));
		$this->assertEquals('C:\\a\\b\\c\\d\\', (string) (new Path('c\\d\\'))->toAbsolute('C:\\a\\b', ['separator' => '\\', 'preserveSlash' => true]));
	}

	public function test_toAbsolute_should_return_correct_result_with_a_slash_at_the_end_when_there_is_no_a_slash_and_trailingSlash_is_true(): void {
		$this->assertEquals('/a/b/c/', (string) (new Path('b/c'))->toAbsolute('/a', ['separator' => '/', 'trailingSlash' => true]));
		$this->assertEquals('C:\\a\\b\\c\\d\\', (string) (new Path('c\\d'))->toAbsolute('C:\\a\\b', ['separator' => '\\', 'trailingSlash' => true]));
	}

	public function test_toAbsolute_should_return_correct_result_when_baseResolve_is_true_and_base_does_not_have_slash(): void {
		$this->assertEquals('/b/c', (string) (new Path('b/c'))->toAbsolute('/a', ['separator' => '/', 'baseResolve' => true]));
		$this->assertEquals('C:\\a\\c\\d', (string) (new Path('c\\d'))->toAbsolute('C:\\a\\b', ['separator' => '\\', 'baseResolve' => true]));
	}

	public function test_toAbsolute_should_return_correct_result_when_baseResolve_is_true_and_base_have_slash(): void {
		$this->assertEquals('/a/b/c', (string) (new Path('b/c'))->toAbsolute('/a/', ['separator' => '/', 'baseResolve' => true]));
		$this->assertEquals('C:\\a\\b\\c\\d', (string) (new Path('c\\d'))->toAbsolute('C:\\a\\b\\', ['separator' => '\\', 'baseResolve' => true]));
	}

	public function test_toRelative_should_return_the_same_path_when_it_is_relative(): void {
		$this->assertEquals('a/b', (string) (new Path('a/b'))->toRelative('/a', ['separator' => '/']));
	}

	public function test_toRelative_should_throw_an_exception_when_the_base_path_is_relative(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot make the path "/a/b" relative: "a" is relative');
		(new Path('/a/b'))->toRelative('a');
	}

	public function test_toRelative_should_throw_an_exception_when_the_separator_is_invalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot use separator " ": only "\\" and "/" are allowed');
		(new Path('/a/b'))->toRelative('/a', ['separator' => ' ']);
	}

	public function test_toRelative_should_return_correct_result_when_the_base_is_root(): void {
		foreach (self::ROOT_PATHS as $root)
			$this->assertEquals('a/b', (string) (new Path($root.'/a/b'))->toRelative($root, ['separator' => '/']));
	}

	public function test_toRelative_should_return_correct_result_when_the_separator_is_specified(): void {
		$this->assertEquals('c', (string) (new Path('/a/b/c'))->toRelative('\\a\\b', ['separator' => '/']));
	}

	public function test_toRelative_should_return_correct_result_without_a_slash_at_the_end_when_there_is_no_a_slash_and_preserveSlash_is_true(): void {
		$this->assertEquals('b/c', (string) (new Path('/a/b/c'))->toRelative('/a', ['separator' => '/', 'preserveSlash' => true]));
		$this->assertEquals('c\\d', (string) (new Path('C:/a/b/c\\d'))->toRelative('C:\\a\\b', ['separator' => '\\', 'preserveSlash' => true]));
	}

	public function test_toRelative_should_return_correct_result_with_a_slash_at_the_end_when_there_is_a_slash_and_preserveSlash_is_true(): void {
		$this->assertEquals('b/c/', (string) (new Path('/a/b/c/'))->toRelative('\\a', ['separator' => '/', 'preserveSlash' => true]));
		$this->assertEquals('c\\d\\', (string) (new Path('C:/a/b/c\\d\\'))->toRelative('C:/a\\b', ['separator' => '\\', 'preserveSlash' => true]));
	}

	public function test_toRelative_should_return_correct_result_with_a_slash_at_the_end_when_there_is_no_a_slash_and_trailingSlash_is_true(): void {
		$this->assertEquals('c/d/', (string) (new Path('/a/b/c/d'))->toRelative('\\a/b', ['separator' => '/', 'trailingSlash' => true]));
		$this->assertEquals('c\\d\\', (string) (new Path('C:/a/b/c\\d'))->toRelative('C:/a\\b', ['separator' => '\\', 'trailingSlash' => true]));
	}

	public function test_toRelative(): void {
		$this->assertEquals('a', (new Path('/a'))->toRelative('/'));
		$this->assertEquals('a', (new Path('/a/'))->toRelative('/'));
		$this->assertEquals('a', (new Path('C:/a'))->toRelative('C:/'));
	}

	public function test_isAbsolute_should_return_true_when_the_path_is_root(): void {
		foreach (self::ROOT_PATHS as $root)
			$this->assertTrue((new Path($root))->isAbsolute());
	}

	public function test_isAbsolute_should_return_true_when_the_path_is_absolute(): void {
		$this->assertTrue((new Path('/var'))->isAbsolute());
		$this->assertTrue((new Path('C:\\Windows'))->isAbsolute());
		$this->assertTrue((new Path('/.'))->isAbsolute());
		$this->assertTrue((new Path('\\..'))->isAbsolute());
	}

	public function test_isAbsolute_should_return_false_when_the_path_is_relative(): void {
		$this->assertFalse((new Path('var'))->isAbsolute());
		$this->assertFalse((new Path('Windows\\System32'))->isAbsolute());
	}

	public function test_isAbsolute_should_return_false_when_the_path_is_dots(): void {
		$this->assertFalse((new Path('.'))->isAbsolute());
		$this->assertFalse((new Path('..'))->isAbsolute());
	}

	public function test_isRelative_should_return_false_when_the_path_is_root(): void {
		foreach (self::ROOT_PATHS as $root)
			$this->assertFalse((new Path($root))->isRelative());
	}

	public function test_isRelative_should_return_false_when_the_path_is_absolute(): void {
		$this->assertFalse((new Path('/var'))->isRelative());
		$this->assertFalse((new Path('C:\\Windows'))->isRelative());
		$this->assertFalse((new Path('/.'))->isRelative());
		$this->assertFalse((new Path('\\..'))->isRelative());
	}

	public function test_isRelative_should_return_true_when_the_path_is_relative(): void {
		$this->assertTrue((new Path('var'))->isRelative());
		$this->assertTrue((new Path('Windows\\System32'))->isRelative());
	}

	public function test_isRelative_should_return_true_when_the_path_is_dots(): void {
		$this->assertTrue((new Path('.'))->isRelative());
		$this->assertTrue((new Path('..'))->isRelative());
	}

	public function test_isRoot_should_return_true_when_the_path_is_root(): void {
		foreach (self::ROOT_PATHS as $root)
			$this->assertTrue((new Path($root))->isRoot());
	}

	public function test_isRoot_should_return_false_when_the_path_is_absolute(): void {
		$this->assertFalse((new Path('/var'))->isRoot());
		$this->assertFalse((new Path('C:\\Windows'))->isRoot());
		$this->assertFalse((new Path('/.'))->isRoot());
		$this->assertFalse((new Path('\\..'))->isRoot());
	}

	public function test_isRoot_should_return_false_when_the_path_is_relative(): void {
		$this->assertFalse((new Path('var'))->isRoot());
		$this->assertFalse((new Path('Windows\\System32'))->isRoot());
	}

	public function test_isRoot_should_return_false_when_the_path_is_dots(): void {
		$this->assertFalse((new Path('.'))->isRoot());
		$this->assertFalse((new Path('..'))->isRoot());
	}

	public function test_resolve_should_throw_an_exception_when_there_are_too_many_parent_jumps(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot resolve path "/../var": too many parent jumps');
		Path::resolve(['/../var']);
	}

	public function test_resolve_should_throw_an_exception_when_there_are_too_many_parent_jumps_and_the_path_is_drive(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot resolve path "C:/..": too many parent jumps');
		Path::resolve(['C:/..']);
	}

	public function test_resolve_should_throw_an_exception_when_the_separator_is_invalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot use separator " ": only "\\" and "/" are allowed');
		Path::resolve(['a', 'b'], ['separator' => ' ']);
	}

	public function test_resolve_should_return_correct_result_when_baseResolve_is_true(): void {
		$this->assertEquals('/a/b/d', Path::resolve(['/a/b/c', 'd'], ['separator' => '/', 'baseResolve' => true]));
		$this->assertEquals('C:\\a\\b\\d\\e', Path::resolve(['C:', 'a/b\\c', 'd\\e'], ['separator' => '\\', 'baseResolve' => true]));
	}

	public function test_resolve_should_skip_empty_strings(): void {
		$this->assertEquals('/a/b/c', Path::resolve(['', '/a/b', '', 'c'], ['separator' => '/']));
	}

	public function test_resolve_should_return_empty_string_when_the_paths_is_empty(): void {
		$this->assertEmpty(Path::resolve([]));
		$this->assertEmpty(Path::resolve(['']));
	}

	public function test_resolve_should_preserve_slash_when_preserveSlash_is_true_and_the_last_path_ends_with_slash(): void {
		$this->assertEquals('/a/b/c/', Path::resolve(['/a/b', 'c/'], ['separator' => '/', 'preserveSlash' => true]));
	}

	public function test_resolve_should_return_correct_result(): void {
		$this->assertEquals('/a/b', Path::resolve(['/', 'a', 'b'], ['separator' => '/']));
		$this->assertEquals('C:/a/b', Path::resolve(['C:', 'a', 'b'], ['separator' => '/']));
		$this->assertEquals('C:/a/b', Path::resolve(['C:\\', 'a', 'b'], ['separator' => '/']));
		$this->assertEquals('b/c', Path::resolve(['a', 'b/c'], ['separator' => '/', 'baseResolve' => true]));
		$this->assertEquals('/b/c', Path::resolve(['/a', 'b/c'], ['separator' => '/', 'baseResolve' => true]));
	}

	public function test_normalize_should_return_an_empty_string_when_the_path_is_empty(): void {
		$this->assertEmpty(Path::normalize(''));
	}

	public function test_normalize_should_return_an_empty_string_when_the_path_is_dot(): void {
		$this->assertEmpty(Path::normalize('.'));
	}

	public function test_normalize_should_throw_an_exception_when_the_path_is_a_parent_jump(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot resolve path "..": too many parent jumps');
		Path::normalize('..');
	}

	public function test_normalize_should_throw_an_exception_when_there_are_too_many_parent_jumps(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot resolve path "/a/../..": too many parent jumps');
		Path::normalize('/a/../..');
	}

	public function test_normalize_should_throw_an_exception_when_the_separator_is_invalid(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Cannot use separator " ": only "\\" and "/" are allowed');
		Path::normalize('', ['separator' => ' ']);
	}

	public function test_normalize_should_throw_an_exception_when_jumping_out_of_drive(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot resolve path "C:/..": too many parent jumps');
		Path::normalize('C:/..');
	}

	public function test_normalize_should_collapse_redundant_separators(): void {
		$this->assertEquals('/', Path::normalize('///', ['separator' => '/']));
		$this->assertEquals('\\', Path::normalize('///', ['separator' => '\\']));
		$this->assertEquals('a/b', Path::normalize('a//b', ['separator' => '/']));
		$this->assertEquals('/a/b', Path::normalize('/a//b', ['separator' => '/']));
		$this->assertEquals('\\a\\b', Path::normalize('/a//b', ['separator' => '\\']));
		$this->assertEquals('C:\\a\\b', Path::normalize('C:/a//b', ['separator' => '\\']));
	}

	public function test_normalize_should_use_specified_separator(): void {
		$this->assertEquals('/a/b', Path::normalize('/a/b', ['separator' => '/']));
		$this->assertEquals('\\a\\b', Path::normalize('/a/b', ['separator' => '\\']));
	}

	public function test_normalize_should_preserve_trailing_slash_when_preserveSlash_is_true_and_path_ends_with_slash(): void {
		$this->assertEquals('/a/b/', Path::normalize('/a/b/', ['separator' => '/', 'preserveSlash' => true]));
	}

	public function test_normalize_should_not_preserve_trailing_slash_when_preserveSlash_is_true_and_path_does_not_end_with_slash(): void {
		$this->assertEquals('/a/b', Path::normalize('/a/b', ['separator' => '/', 'preserveSlash' => true]));
	}

	public function test_normalize_should_add_trailing_slash_when_trailingSlash_is_true(): void {
		$this->assertEquals('/a/b/', Path::normalize('/a/b', ['separator' => '/', 'trailingSlash' => true]));
	}

	public function test_normalize_should_correctly_remove_parent_jumps(): void {
		$this->assertEquals('a/b/c', Path::normalize('a/d/../b/e/f/g/../../../c', ['separator' => '/']));
	}

	public function test_normalize_should_return_relative_path_when_the_path_is_relative(): void {
		$this->assertEquals('a/b/c', Path::normalize('a//b\\c/', ['separator' => '/']));
	}

	public function test_normalize_should_return_absolute_path_when_the_path_is_absolute(): void {
		$this->assertEquals('/a/b/c', Path::normalize('/a//b\\c/', ['separator' => '/']));
		$this->assertEquals('C:/a/b/c', Path::normalize('C:/a//b\\c/', ['separator' => '/']));
	}

	public function test_normalize_should_return_drive_with_slash_at_the_end(): void {
		$this->assertEquals('C:\\', Path::normalize('C:', ['separator' => '\\']));
		$this->assertEquals('C:\\', Path::normalize('C:\\a\\b/../..', ['separator' => '\\']));
	}

	public function test_normalize(): void {
		$this->assertEquals('a', Path::normalize('a'));
	}
}

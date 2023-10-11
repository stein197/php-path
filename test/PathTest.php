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
describe('\\Stein197\\Path::isAbsolute()', function () {})->skip();
describe('\\Stein197\\Path::isRelative()', function () {})->skip();
describe('\\Stein197\\Path::isRoot()', function () {})->skip();
describe('\\Stein197\\Path::getParent()', function () {})->skip();
describe('\\Stein197\\Path::getType()', function () {})->skip();
describe('\\Stein197\\Path::toAbsolute()', function () {})->skip();
describe('\\Stein197\\Path::toRelative()', function () {})->skip();
describe('\\Stein197\\Path::format()', function () {})->skip();
describe('\\Stein197\\Path::resolve()', function () {})->skip();
describe('\\Stein197\\Path::normalize()', function () {})->skip();

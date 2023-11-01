# CHANGELOG
## [2.0.0](../../compare/1.0.0..2.0.0) - 2023-11-01
### Added
- Option key constants `Path::OPTKEY_SEPARATOR`, `PATH::OPTKEY_TRAILING_SLASH`
- Public readonly properties: `isDOS`, `isUnix`, `isRoot`, `isAbsolute`, `isRelative`, `depth`, `drive`, `path`
- Instance methods: `getElement()`, `getSubpath()`, `format()`, `startsWith()`, `endsWith()`, `isChildOf()`, `isParentOf()`, `includes()`, `firstIndexOf()`, `lastIndexOf()`
- Static methods: `join()`, `expand()`, `new()`, `findCommonBase()`

### Changed
- Replaced PHPUnit with Pest
- Path instances are now read-only
- A path object now can be instantiated via static `Path::new()` method
- The `Path` class implements the next interfaces: `ArrayAccess`, `Countable`, `Iterator`, `Stringable`, `Stein197\Equalable`
- The `Path` class was moved to `Stein197\FileSystem` namespace. Now its fully qualified name is `Stein197\FileSystem\Path`
- The `Path::resolve()` was renamed to `Path::join()`
- `Path::normalize()` doesn't throw exceptions anymore

### Removed
- Options `preserveSlash`, `baseResolve`

## [1.0.0](../../1.0.0) - 2022-04-24
Release

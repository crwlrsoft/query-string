# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2023-01-30
### Fixed
- When creating a `Query` from string, and it contains empty key value parts, like `&foo=bar`, `foo=bar&` or `foo=bar&&baz=quz`, the unnecessary `&` characters are removed in the string version now. For example, `&foo=bar` previously lead to `$instance->toString()` returning `&foo=bar` and now it returns `foo=bar`.
- To assure that there can't be differences in the array and string versions returned by the `Query` class, no matter if the instance was created from string or array, the library now first converts incoming values back and forth. So, when an instance is created from string, it first converts it to an array and then again back to a string and vice versa when an instance is created from an array. Some Examples being fixed by this:
  - From string `   foo=bar  `:
    - Before: toString(): `+++foo=bar++`, toArray(): `['foo' => 'bar  ']`.
    - Now: toString(): `foo=bar++`, toArray(): `['foo' => 'bar  ']`
  - From string `foo[bar] [baz]=bar`
    - Before: toString(): `foo%5Bbar%5D+%5Bbaz%5D=bar`, toArray(): `['foo' => ['bar' => 'bar']]`.
    - Now: toString(): `foo%5Bbar%5D=bar`, toArray(): `'[foo' => ['bar' => 'bar']]`.
  - From string `foo[bar][baz][]=bar&foo[bar][baz][]=foo`
    - Before: toString(): `foo%5Bbar%5D%5Bbaz%5D%5B%5D=bar&foo%5Bbar%5D%5Bbaz%5D%5B%5D=foo`, toArray(): `['foo' => ['bar' => ['baz' => ['bar', 'foo']]]]`.
    - Now: toString(): `foo%5Bbar%5D%5Bbaz%5D%5B0%5D=bar&foo%5Bbar%5D%5Bbaz%5D%5B1%5D=foo`, toArray(): `['foo' => ['bar' => ['baz' => ['bar', 'foo']]]]`.
  - From string `option`
    - Before: toString(): `option`, toArray(): `['option' => '']`
    - Now: toString(): `option=`, toArray(): `['option' => '']`
  - From string `foo=bar=bar==`
    - Before: toString(): `foo=bar=bar==`, toArray(): `[['foo' => 'bar=bar==']`
    - Now: toString(): `foo=bar%3Dbar%3D%3D`, toArray(): `[['foo' => 'bar=bar==']`
  - From string `sum=10%5c2%3d5`
    - Before: toString(): `sum=10%5c2%3d5`, toArray(): `[['sum' => '10\\2=5']`
    - Now: toString(): `sum=10%5C2%3D5`, toArray(): `[['sum' => '10\\2=5']`
  - From string `foo=%20+bar`
    - Before: toString(): `foo=%20+bar`, toArray(): `['foo' => '  bar']`
    - Now: toString(): `foo=++bar`, toArray(): `['foo' => '  bar']`
- Maintain the correct order of key value pairs when converting query string to array.

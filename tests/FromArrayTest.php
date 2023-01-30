<?php

use Crwlr\QueryString\Query;

it('parses a simple array with one key value pair', function () {
    expect(Query::fromArray(['foo' => 'bar'])->toString())->toBe('foo=bar');
});

it('parses an array with multiple key value pairs', function () {
    expect(Query::fromArray(['foo' => 'bar', 'yo' => 'lo', 'key' => 'val'])->toString())
        ->toBe('foo=bar&yo=lo&key=val');
});

it('parses an array with a second level without string keys', function () {
    expect(Query::fromArray(['foo' => ['bar', 'baz', 'qux']])->toString())
        ->toBe('foo%5B0%5D=bar&foo%5B1%5D=baz&foo%5B2%5D=qux');
});

it('parses an array with multiple levels and string keys', function () {
    expect(Query::fromArray(['a' => ['a' => ['a' => ['1', '2']]], 'b' => '3'])->toString())
        ->toBe('a%5Ba%5D%5Ba%5D%5B0%5D=1&a%5Ba%5D%5Ba%5D%5B1%5D=2&b=3');
});

it('handles encoding', function () {
    $queryString = Query::fromArray(['föó' => 'bär']);

    expect($queryString->toString())->toBe('f%C3%B6%C3%B3=b%C3%A4r');

    expect($queryString->toArray())->toBe(['föó' => 'bär']);
});

it('sanitizes array input', function () {
    $queryString = Query::fromArray(['   foo' => 'bar  ']);

    expect($queryString->toArray())->toBe(['foo' => 'bar  ']);

    expect($queryString->toString())->toBe('foo=bar++');
});

it(
    // See https://github.com/brefphp/bref/pull/1383
    'correctly handles all the test cases from bref, related to keys containing dots',
    function (array $queryArray, string $expectedQueryString) {
        $queryString = Query::fromArray($queryArray);

        expect($queryString->toString())->toBe($expectedQueryString);
    }
)->with([
    [['foo' => 'bar', 'baz.bar' => 'foo'], 'foo=bar&baz.bar=foo'],
    [
        [
            'foo' => ['bar', 'baz'],
            'cards' => ['birthday'],
            'colors' => [['red'], ['blue']],
            'shapes' => ['a' => ['square', 'triangle']],
            'myvar' => 'abc',
            'foo.bar' => ['baz'],
        ],
        'foo%5B0%5D=bar&foo%5B1%5D=baz&cards%5B0%5D=birthday&colors%5B0%5D%5B0%5D=red&colors%5B1%5D%5B0%5D=blue' .
        '&shapes%5Ba%5D%5B0%5D=square&shapes%5Ba%5D%5B1%5D=triangle&myvar=abc&foo.bar%5B0%5D=baz',
    ],
    [
        [
            'vars' => [
                'val1' => 'foo',
                'val2' => ['bar'],
            ],
            'foo.bar' => ['baz'],
        ],
        'vars%5Bval1%5D=foo&vars%5Bval2%5D%5B0%5D=bar&foo.bar%5B0%5D=baz',
    ],
    [
        ['foo_bar' => '2'],
        'foo_bar=2',
    ],
    [
        ['foo_bar' => 'v1', 'foo.bar' => 'v2'],
        'foo_bar=v1&foo.bar=v2',
    ],
    [
        ['foo_bar' => 'v1', 'foo.bar' => 'v2', 'foo.bar_extra' => 'v3', 'foo_bar3' => 'v4'],
        'foo_bar=v1&foo.bar=v2&foo.bar_extra=v3&foo_bar3=v4',
    ],
    [
        ['foo_bar.baz' => 'v1'],
        'foo_bar.baz=v1',
    ],
    [
        ['foo_bar' => 'v1', 'k' => ['foo.bar' => 'v2']],
        'foo_bar=v1&k%5Bfoo.bar%5D=v2',
    ],
    [
        ['k.1' => 'v.1', 'k.2' => ['s.k1' => 'v.2', 's.k2' => 'v.3']],
        'k.1=v.1&k.2%5Bs.k1%5D=v.2&k.2%5Bs.k2%5D=v.3',
    ],
    [
        ['foo.bar' => ['v1'], 'foo.bar_extra' => ['v2'], 'foo.bar.extra' => ['v3']],
        'foo.bar%5B0%5D=v1&foo.bar_extra%5B0%5D=v2&foo.bar.extra%5B0%5D=v3',
    ],

    // test cases from FromStringTest, reversed
    [['foo' => 'bar'], 'foo=bar'],
    [['foo' => 'bar  '], 'foo=bar++'],
    [['?foo' => 'bar'], '%3Ffoo=bar'],
    [['#foo' => 'bar'], '%23foo=bar'],
    [['foo' => 'bar'], 'foo=bar'],
    [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo'],
    [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo'],
    [['foo' => ['bar' => ['baz' => ['bax' => 'bar']]]], 'foo%5Bbar%5D%5Bbaz%5D%5Bbax%5D=bar'],
    [['foo' => ['bar' => 'bar']], 'foo%5Bbar%5D=bar'],
    [['foo' => ['bar' => ['baz' => ['bar', 'foo']]]], 'foo%5Bbar%5D%5Bbaz%5D%5B0%5D=bar&foo%5Bbar%5D%5Bbaz%5D%5B1%5D=foo'],
    [['foo' => ['bar' => [['bar'], ['foo']]]], 'foo%5Bbar%5D%5B0%5D%5B0%5D=bar&foo%5Bbar%5D%5B1%5D%5B0%5D=foo'],
    [['option' => ''], 'option='],
    [['option' => '0'], 'option=0'],
    [['option' => '1'], 'option=1'],
    [['foo' => 'bar=bar=='], 'foo=bar%3Dbar%3D%3D'],
    [['options' => ['option' => '0']], 'options%5Boption%5D=0'],
    [['options' => ['option' => 'foobar']], 'options%5Boption%5D=foobar'],
    [['sum' => '10\\2=5'], 'sum=10%5C2%3D5'],
    // Special cases
    [
        [
            'a' => '<==  foo bar  ==>',
            'b' => '###Hello World###',
        ],
        'a=%3C%3D%3D++foo+bar++%3D%3D%3E&b=%23%23%23Hello+World%23%23%23',
    ],
    [
        ['str' => "A string with containing \0\0\0 nulls"],
        'str=A+string+with+containing+%00%00%00+nulls',
    ],
    [
        [
            'arr_1' => 'sid',
            'arr' => ['4' => 'fred'],
        ],
        'arr_1=sid&arr%5B4%5D=fred',
    ],
    [
        [
            'arr_1' => 'sid',
            'arr' => ['4' => ['[2' => 'fred']],
        ],
        'arr_1=sid&arr%5B4%5D%5B%5B2%5D=fred',
    ],
]);

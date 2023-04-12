<?php

use Crwlr\QueryString\Query;

it('parses a simple string with one key value pair', function () {
    expect((new Query('foo=bar'))->toArray())->toBe(['foo' => 'bar']);
});

it('parses a query string with multiple key value pairs', function () {
    expect((new Query('foo=bar&yo=lo&key=val'))->toArray())->toBe(['foo' => 'bar', 'yo' => 'lo', 'key' => 'val']);
});

it('parses a query string with array values', function () {
    expect((new Query('foo[]=bar&foo[]=baz&foo[]=qux'))->toArray())->toBe(['foo' => ['bar', 'baz', 'qux']]);
});

it('parses a query string with arrays multiple levels deep', function () {
    expect((new Query('a[a][a][]=1&a[a][a][]=2&b=3'))->toArray())
        ->toBe(['a' => ['a' => ['a' => ['1', '2']]], 'b' => '3']);
});

it('encodes percent characters that aren\'t part of percent encoded characters', function () {
    expect((new Query('f%C3%93%%C3%93%=%b%C3%A0%r%'))->toString())
        ->toBe('f%C3%93%25%C3%93%25=%25b%C3%A0%25r%25');
});

it('encodes characters in string', function () {
    expect((new Query('föó=bär'))->toString())->toBe('f%C3%B6%C3%B3=b%C3%A4r');
});

it('unencodes characters in array', function () {
    expect((new Query('f%C3%B6%C3%B3=b%C3%A4r'))->toArray())->toBe(['föó' => 'bär']);
});

it('encodes percent characters that aren\'t part of percent encoded characters in array keys', function () {
    $queryString = new Query('foo[ba%r]=baz');

    expect($queryString->toString())->toBe('foo%5Bba%25r%5D=baz');
});

it('encodes characters in array keys in string', function () {
    expect((new Query('foo[bär]=baz'))->toString())->toBe('foo%5Bb%C3%A4r%5D=baz');
});

it('unencodes characters in array keys in array', function () {
    expect((new Query('foo%5Bb%C3%A4r%5D=baz'))->toArray())->toBe(['foo' => ['bär' => 'baz']]);
});

it('does not convert dots and spaces in keys to underscores', function () {
    $queryString = new Query('fo.o[b ar]=baz&fo o[b.ar][b.a z]=quz');

    expect($queryString->toString())->toBe('fo.o%5Bb+ar%5D=baz&fo+o%5Bb.ar%5D%5Bb.a+z%5D=quz');

    expect($queryString->toArray())->toBe([
        'fo.o' => ['b ar' => 'baz'],
        'fo o' => ['b.ar' => ['b.a z' => 'quz']]
    ]);
});

it('maintains the correct order of key value pairs', function () {
    $queryString = new Query('foo_bar=v1&foo.bar=v2&foo.bar_extra=v3&foo_bar3=v4');

    expect($queryString->toArray())->toBe([
        'foo_bar' => 'v1',
        'foo.bar' => 'v2',
        'foo.bar_extra' => 'v3',
        'foo_bar3' => 'v4',
    ]);
});

it(
    // See https://github.com/brefphp/bref/pull/1383
    'correctly handles all the test cases from bref, related to keys containing dots',
    function (array $array, string $normalized, string $raw) {
        $query = Query::fromString($raw);

        expect($query->toString())->toBe($normalized);

        expect($query->toArray())->toBe($array);
    }
)->with([
    [['foo' => 'bar'], 'foo=bar', 'foo=bar'],
    [['foo' => 'bar  '], 'foo=bar++', '   foo=bar  '],
    [['?foo' => 'bar'], '%3Ffoo=bar', '?foo=bar'],
    [['#foo' => 'bar'], '%23foo=bar', '#foo=bar'],
    [['foo' => 'bar'], 'foo=bar', '&foo=bar'],
    [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo', 'foo=bar&bar=foo'],
    [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo', 'foo=bar&&bar=foo'],
    [['foo' => ['bar' => ['baz' => ['bax' => 'bar']]]], 'foo%5Bbar%5D%5Bbaz%5D%5Bbax%5D=bar', 'foo[bar][baz][bax]=bar'],
    [['foo' => ['bar' => 'bar']], 'foo%5Bbar%5D=bar', 'foo[bar] [baz]=bar'],
    [
        ['foo' => ['bar' => ['baz' => ['bar', 'foo']]]],
        'foo%5Bbar%5D%5Bbaz%5D%5B0%5D=bar&foo%5Bbar%5D%5Bbaz%5D%5B1%5D=foo',
        'foo[bar][baz][]=bar&foo[bar][baz][]=foo',
    ],
    [['foo' => ['bar' => [['bar'], ['foo']]]], 'foo%5Bbar%5D%5B0%5D%5B0%5D=bar&foo%5Bbar%5D%5B1%5D%5B0%5D=foo', 'foo[bar][][]=bar&foo[bar][][]=foo'],
    [['option' => ''], 'option=', 'option'],
    [['option' => '0'], 'option=0', 'option=0'],
    [['option' => '1'], 'option=1', 'option=1'],
    [['foo' => 'bar=bar=='], 'foo=bar%3Dbar%3D%3D', 'foo=bar=bar=='],
    [['options' => ['option' => '0']], 'options%5Boption%5D=0', 'options[option]=0'],
    [['options' => ['option' => 'foobar']], 'options%5Boption%5D=foobar', 'options[option]=foobar'],
    [['sum' => '10\\2=5'], 'sum=10%5C2%3D5', 'sum=10%5c2%3d5'],

    // Special cases
    [
        [
            'a' => '<==  foo bar  ==>',
            'b' => '###Hello World###',
        ],
        'a=%3C%3D%3D++foo+bar++%3D%3D%3E&b=%23%23%23Hello+World%23%23%23',
        'a=%3c%3d%3d%20%20foo+bar++%3d%3d%3e&b=%23%23%23Hello+World%23%23%23',
    ],
    [
        ['str' => "A string with containing \0\0\0 nulls"],
        'str=A+string+with+containing+%00%00%00+nulls',
        'str=A%20string%20with%20containing%20%00%00%00%20nulls',
    ],
    [
        [
            'arr_1' => 'sid',
            'arr' => ['4' => 'fred'],
        ],
        'arr_1=sid&arr%5B4%5D=fred',
        'arr[1=sid&arr[4][2=fred',
    ],
    [
        [
            'arr_1' => 'sid',
            'arr' => ['4' => ['[2' => 'fred']],
        ],
        'arr_1=sid&arr%5B4%5D%5B%5B2%5D=fred',
        'arr[1=sid&arr[4][[2][3[=fred',
    ],

    // Test cases from FromArrayTest, reversed
    [['foo' => 'bar', 'baz.bar' => 'foo'], 'foo=bar&baz.bar=foo', 'foo=bar&baz.bar=foo'],
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
        'foo[0]=bar&foo[1]=baz&cards[0]=birthday&colors[0][0]=red&colors[1][0]=blue&shapes[a][0]=square' .
        '&shapes[a][1]=triangle&myvar=abc&foo.bar[0]=baz',
    ],
    [
        ['vars' => ['val1' => 'foo', 'val2' => ['bar']], 'foo.bar' => ['baz']],
        'vars%5Bval1%5D=foo&vars%5Bval2%5D%5B0%5D=bar&foo.bar%5B0%5D=baz',
        'vars[val1]=foo&vars[val2][0]=bar&foo.bar[0]=baz',
    ],
    [['foo_bar' => '2'], 'foo_bar=2', 'foo_bar=2'],
    [['foo_bar' => 'v1', 'foo.bar' => 'v2'], 'foo_bar=v1&foo.bar=v2', 'foo_bar=v1&foo.bar=v2'],
    [
        ['foo_bar' => 'v1', 'foo.bar' => 'v2', 'foo.bar_extra' => 'v3', 'foo_bar3' => 'v4'],
        'foo_bar=v1&foo.bar=v2&foo.bar_extra=v3&foo_bar3=v4',
        'foo_bar=v1&foo.bar=v2&foo.bar_extra=v3&foo_bar3=v4',
    ],
    [['foo_bar.baz' => 'v1'], 'foo_bar.baz=v1', 'foo_bar.baz=v1'],
    [
        ['foo_bar' => 'v1', 'k' => ['foo.bar' => 'v2']],
        'foo_bar=v1&k%5Bfoo.bar%5D=v2',
        'foo_bar=v1&k[foo.bar]=v2',
    ],
    [
        ['k.1' => 'v.1', 'k.2' => ['s.k1' => 'v.2', 's.k2' => 'v.3']],
        'k.1=v.1&k.2%5Bs.k1%5D=v.2&k.2%5Bs.k2%5D=v.3',
        'k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3',
    ],
    [
        ['foo.bar' => ['v1'], 'foo.bar_extra' => ['v2'], 'foo.bar.extra' => ['v3']],
        'foo.bar%5B0%5D=v1&foo.bar_extra%5B0%5D=v2&foo.bar.extra%5B0%5D=v3',
        'foo.bar[0]=v1&foo.bar_extra[0]=v2&foo.bar.extra[0]=v3',
    ],
]);

test('Duplicate query string keys are converted to arrays', function () {
    expect(Query::fromString('test=1&test2=2&test=2&test[]=3&test[test]=4')->toArray())
        ->toEqual([
            'test' => [1, 2, 3, 'test' => 4],
            'test2' => 2,
        ]);
});

it('correctly parses array syntax when brackets are encoded', function () {
    expect(
        Query::fromString(
            'filter%5Bdestination%5D%5B%5D=101&filter%5Bdestination%5D%5B%5D=103&filter%5Bdestination%5D%5B%5D=106'
        )->toArray()
    )->toBe([
        'filter' => [
            'destination' => ['101', '103', '106']
        ]
    ]);
});

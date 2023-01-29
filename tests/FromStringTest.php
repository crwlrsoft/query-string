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

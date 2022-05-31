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

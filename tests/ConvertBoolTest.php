<?php

use Crwlr\QueryString\Query;

it('converts boolean values to int by default', function () {
    expect(Query::fromArray(['foo' => true, 'bar' => false])->toString())->toBe('foo=1&bar=0');
});

it('converts boolean values to string when using boolToString() method', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    $query->boolToString();

    expect($query->toString())->toBe('foo=true&bar=false');
});

it('does not convert boolean values to string when boolToInt() method was used last', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    $query->boolToString();

    $query->boolToInt();

    expect($query->toString())->toBe('foo=1&bar=0');
});

it('regenerates the query string when boolToString() was used', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    expect($query->toString())->toBe('foo=1&bar=0');

    $query->boolToString();

    expect($query->toString())->toBe('foo=true&bar=false');
});

it('regenerates the query string when boolToInt() was used after using boolToString()', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    $query->boolToString();

    expect($query->toString())->toBe('foo=true&bar=false');

    $query->boolToInt();

    expect($query->toString())->toBe('foo=1&bar=0');
});

it('changes bools to string on all levels', function () {
    $query = Query::fromArray([
        'a' => true,
        'b' => ['b' => false],
        'c' => ['c' => ['c' => true]],
        'd' => ['d' => ['d' => ['d' => false]]],
    ]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=1&b[b]=0&c[c][c]=1&d[d][d][d]=0');

    $query->boolToString();

    expect($query->toStringWithUnencodedBrackets())->toBe('a=true&b[b]=false&c[c][c]=true&d[d][d][d]=false');
});

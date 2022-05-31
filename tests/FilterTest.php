<?php

use Crwlr\QueryString\Query;

it('filters a query array', function () {
    $query = Query::fromString('a=12&b=3&c=7&d=27&e=16');

    $query->filter(function ($value) {
        return (int) $value > 10;
    });

    expect($query->toString())->toBe('a=12&d=27&e=16');
});

test('filtering a child updates the parent query', function () {
    $query = Query::fromString('a[a]=1&a[b]=2&a[c]=3&a[d]=5&a[e]=8&a[f]=13');

    $query->get('a')->filter(function ($value) {
        return (int) $value > 5;
    });

    expect($query->toStringWithUnencodedBrackets())->toBe('a[e]=8&a[f]=13');
});

it('also passes the keys to the filter callback', function () {
    $query = Query::fromString('foo=a&bar=b&baz=c');

    $query->filter(function ($value, $key) {
        return $key !== 'bar';
    });

    expect($query->toString())->toBe('foo=a&baz=c');
});

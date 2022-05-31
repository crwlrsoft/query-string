<?php

use Crwlr\QueryString\Query;

it('maps query values', function () {
    $query = Query::fromArray(['a' => 3, 'b' => 4, 'c' => 5]);

    $query->map(function ($value) {
        return (int) $value - 2;
    });

    expect($query->toString())->toBe('a=1&b=2&c=3');
});

test('mapping values in a child array updates the parent query string', function () {
    $query = Query::fromArray(['a' => ['a' => 3, 'b' => 4, 'c' => 5]]);

    $query->get('a')->map(function ($value) {
        return (int) $value - 2;
    });

    expect($query->toStringWithUnencodedBrackets())->toBe('a[a]=1&a[b]=2&a[c]=3');
});

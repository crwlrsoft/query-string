<?php

use Crwlr\QueryString\Query;

it('persists the bool conversion setting in child instances', function () {
    $query = Query::fromArray(['a' => ['b' => true, 'c' => ['d' => false]]]);

    $query->boolToString();

    expect($query->toStringWithUnencodedBrackets())->toBe('a[b]=true&a[c][d]=false');

    expect($query->get('a')->toStringWithUnencodedBrackets())->toBe('b=true&c[d]=false');

    expect($query->get('a')->get('c')->toStringWithUnencodedBrackets())->toBe('d=false');
});

it('changes the bool conversion setting of children when the parent changes', function () {
    $query = Query::fromArray(['a' => ['b' => true, 'c' => ['d' => false]]]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a[b]=1&a[c][d]=0');

    $query->boolToString();

    expect($query->toStringWithUnencodedBrackets())->toBe('a[b]=true&a[c][d]=false');
});

it('does not change the parent bool conversion setting when it changes for a child', function () {
    $query = Query::fromArray(['a' => ['b' => true, 'c' => ['d' => false]]]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a[b]=1&a[c][d]=0');

    $query->get('a')->get('c')->boolToString();

    expect($query->toStringWithUnencodedBrackets())->toBe('a[b]=1&a[c][d]=0');

    expect($query->get('a')->get('c')->toStringWithUnencodedBrackets())->toBe('d=false');
});

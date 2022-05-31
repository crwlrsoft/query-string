<?php

use Crwlr\QueryString\Query;

it('sets a value with a key that doesn\'t exist yet', function () {
    $query = Query::fromArray(['foo' => 'bar']);

    $query->set('baz', 'quz');

    expect($query->toStringWithUnencodedBrackets())->toBe('foo=bar&baz=quz');
});

it('resets a value that already exists', function () {
    $query = Query::fromArray(['foo' => 'bar', 'yo' => 'lo']);

    $query->set('yo', 'lolo');

    expect($query->toStringWithUnencodedBrackets())->toBe('foo=bar&yo=lolo');
});

it('resets an array value that already exists', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['b' => 'b']]);

    $query->set('b', 'b');

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b=b');
});

it('sets an array value', function () {
    $query = Query::fromArray(['a' => 'a']);

    $query->set('b', ['c', 'd']);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[0]=c&b[1]=d');
});

test('when setting an array value, it inherits the parent\'s bool conversion setting', function () {
    $query = Query::fromArray(['a' => true]);

    $query->boolToString();

    $query->set('b', ['c' => true, 'd' => false]);

    expect($query->get('b')->toStringWithUnencodedBrackets())->toBe('c=true&d=false');
});

it('updates the parent when setting something in a child', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['b' => 'b']]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[b]=b');

    $query->get('b')->set('b', 'c');

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[b]=c');
});

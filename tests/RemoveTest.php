<?php

use Crwlr\QueryString\Query;

it('removes a key from a query', function () {
    $query = Query::fromString('foo=eins&bar=zwei&baz=drei');

    $query->remove('bar');

    expect($query->toStringWithUnencodedBrackets())->toBe('foo=eins&baz=drei');
});

test('removing a key from a child also removes it from the parent', function () {
    $query = Query::fromString('foo[bar][a]=baz&foo[bar][b]=quz');

    $query->get('foo')->get('bar')->remove('b');

    expect($query->toStringWithUnencodedBrackets())->toBe('foo[bar][a]=baz');
});

it('removes a certain value from an array', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['c', 'd', 'e', 'd', 'f', 'd']]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[0]=c&b[1]=d&b[2]=e&b[3]=d&b[4]=f&b[5]=d');

    $query->removeValueFrom('b', 'd');

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[0]=c&b[1]=e&b[2]=f');
});

it('updates the parent when removing a value from a child array', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['c' => ['a', 'b', 'c']]]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[c][0]=a&b[c][1]=b&b[c][2]=c');

    $query->get('b')->removeValueFrom('c', 'b');

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[c][0]=a&b[c][1]=c');
});

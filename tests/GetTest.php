<?php

use Crwlr\QueryString\Query;

it('gets a value by key', function () {
    expect(Query::fromArray(['foo' => 'bar'])->get('foo'))->toBe('bar');
});

it('gets a value by numeric index', function () {
    expect(Query::fromArray(['one', 'two'])->get(1))->toBe('two');
});

it('returns null when key doesn\'t exist', function () {
    expect(Query::fromString('foo=bar')->get('baz'))->toBeNull();
});

it('returns a new Query instance when the value for the key is an array', function () {
    $query = Query::fromArray(['foo' => ['bar' => ['baz', 'quz']]]);

    expect($query->get('foo'))->toBeInstanceOf(Query::class);

    expect($query->get('foo')->toArray())->toBe(['bar' => ['baz', 'quz']]);
});

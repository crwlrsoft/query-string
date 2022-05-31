<?php

use Crwlr\QueryString\Query;

it('returns the first value of an array', function () {
    expect(Query::fromArray(['foo' => ['a', 'b', 'c']])->first('foo'))->toBe('a');
});

it('returns the first value of an associative array', function () {
    expect(Query::fromArray(['foo' => ['z' => 'a', 'y' => 'b', 'x' => 'c']])->first('foo'))->toBe('a');
});

it('returns the last value of an array', function () {
    expect(Query::fromArray(['foo' => ['a', 'b', 'c']])->last('foo'))->toBe('c');
});

it('returns the last value of an associative array', function () {
    expect(Query::fromArray(['foo' => ['z' => 'a', 'y' => 'b', 'x' => 'c']])->last('foo'))->toBe('c');
});

test('When first element is an array it returns an instance of the Query class', function () {
    $query = Query::fromArray(['foo' => [['a', 'b'], 'c', 'd']]);

    expect($query->first('foo'))->toBeInstanceOf(Query::class);

    expect($query->first('foo')->toArray())->toBe(['a', 'b']);
});

test('When last element is an array it returns an instance of the Query class', function () {
    $query = Query::fromArray(['foo' => ['a', 'b', ['c', 'd']]]);

    expect($query->last('foo'))->toBeInstanceOf(Query::class);

    expect($query->last('foo')->toArray())->toBe(['c', 'd']);
});

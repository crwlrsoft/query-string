<?php

use Crwlr\QueryString\Query;

test('isArray returns false when key is not an array', function ($value) {
    expect(Query::fromArray(['foo' => $value])->isArray('foo'))->toBeFalse();
})->with([
    'string',
    true,
    false,
    1,
    1.234,
]);

test('isArray returns false when key does not exist', function () {
    expect(Query::fromArray(['foo' => ['test']])->isArray('bar'))->toBeFalse();
});

test('isArray returns true when key is an array', function () {
    expect(Query::fromArray(['foo' => ['test']])->isArray('foo'))->toBeTrue();
});

test('isScalar returns false when key is an array', function () {
    expect(Query::fromArray(['foo' => ['test']])->isScalar('foo'))->toBeFalse();
});

test('isScalar returns false when key is an instance of Query', function () {
    $query = Query::fromArray(['foo' => ['test']]);

    $query->get('foo');

    expect($query->isScalar('foo'))->toBeFalse();
});

test('isScalar returns false when key does not exist', function () {
    expect(Query::fromArray(['foo' => 'test'])->isScalar('bar'))->toBeFalse();
});

test('isScalar returns true when key has a scalar value', function ($value) {
    expect(Query::fromArray(['foo' => $value])->isScalar('foo'))->toBeTrue();
})->with([
    'string',
    true,
    false,
    1,
    1.234,
]);

<?php

use Crwlr\QueryString\Query;

it('returns false when it does not have a certain key', function () {
    expect(Query::fromArray(['foo' => 'a', 'bar' => 'b'])->has('baz'))->toBeFalse();
});

it('returns true when it has a certain key', function () {
    expect(Query::fromArray(['foo' => 'a', 'bar' => 'b'])->has('bar'))->toBeTrue();
});

it('returns true when it does not have a certain numeric index key', function () {
    expect(Query::fromArray(['foo', 'bar'])->has(2))->toBeFalse();
});

it('returns false when it has a certain numeric index key', function () {
    expect(Query::fromArray(['foo', 'bar'])->has(1))->toBeTrue();
});

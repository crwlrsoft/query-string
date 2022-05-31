<?php

use Crwlr\QueryString\Query;

it('uses a different separator when defined', function () {
    $query = Query::fromArray(['foo' => 'a', 'bar' => 'b', 'baz' => 'c']);

    $query->separator(';');

    expect($query->toString())->toBe('foo=a;bar=b;baz=c');
});

it('throws an exception when creating instance from string and setting a custom separator', function () {
    $query = Query::fromString('foo=bar&baz=quz');

    $query->separator('yolo');
})->throws(Exception::class);

it('passes on separator character setting to child instances', function () {
    $query = Query::fromArray(['a' => ['b' => 'foo', 'c' => 'bar']]);

    $query->separator('|');

    $child = $query->get('a');

    expect($child->toString())->toBe('b=foo|c=bar');
});

it('updates separator character of child instances when switching', function () {
    $query = Query::fromArray(['a' => ['b' => 'foo', 'c' => 'bar']]);

    $query->separator('|');

    $child = $query->get('a');

    $query->separator('/');

    expect($child->toString())->toBe('b=foo/c=bar');
});

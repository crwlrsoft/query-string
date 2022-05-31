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

<?php

use Crwlr\QueryString\Query;

it('appends a value to an existing array value', function () {
    $query = Query::fromArray(['letters' => ['a', 'b', 'c']]);

    expect($query->toStringWithUnencodedBrackets())->toBe('letters[0]=a&letters[1]=b&letters[2]=c');

    $query->appendTo('letters', 'd');

    expect($query->toStringWithUnencodedBrackets())->toBe('letters[0]=a&letters[1]=b&letters[2]=c&letters[3]=d');
});

it('makes the value for a key an array when appending to a key that contained a scalar value', function () {
    $query = Query::fromArray(['letters' => 'a']);

    $query->appendTo('letters', 'b');

    expect($query->toStringWithUnencodedBrackets())->toBe('letters[0]=a&letters[1]=b');
});

it('appends an array to a scalar value', function () {
    $query = Query::fromArray(['letters' => 'a']);

    $query->appendTo('letters', ['b', 'c']);

    expect($query->toStringWithUnencodedBrackets())->toBe('letters[0]=a&letters[1]=b&letters[2]=c');
});

it('appends an array to an array', function () {
    $query = Query::fromArray(['letters' => ['a', 'b']]);

    $query->appendTo('letters', ['c', 'd']);

    expect($query->toStringWithUnencodedBrackets())->toBe('letters[0]=a&letters[1]=b&letters[2]=c&letters[3]=d');
});

it('appends a scalar value to an array with a certain key', function () {
    $query = Query::fromArray(['array' => ['foo' => 'bar']]);

    $query->appendTo('array', ['baz' => 'quz']);

    expect($query->toStringWithUnencodedBrackets())->toBe('array[foo]=bar&array[baz]=quz');
});

test('when passing an associative array and a key already exists, it changes it to an array in the query', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['c' => 'c']]);

    $query->appendTo('b', ['c' => 'd']);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[c][0]=c&b[c][1]=d');
});

it('updates the parent when appending something in a child', function () {
    $query = Query::fromArray(['a' => 'a', 'b' => ['c' => 'd']]);

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[c]=d');

    $query->get('b')->appendTo('c', 'e');

    expect($query->toStringWithUnencodedBrackets())->toBe('a=a&b[c][0]=d&b[c][1]=e');
});

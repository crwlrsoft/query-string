<?php

use Crwlr\QueryString\Query;

it('uses RFC3986 space character encoding by default', function () {
    $query = Query::fromArray(['spa ce' => 'char acter']);

    expect($query->toString())->toBe('spa+ce=char+acter');
});

it('uses RFC1738 space character encoding when set', function () {
    $query = Query::fromArray(['spa ce' => 'char acter']);

    $query->spaceCharacterEncoding(PHP_QUERY_RFC3986);

    expect($query->toString())->toBe('spa%20ce=char%20acter');
});

it('uses RFC3986 space character encoding when reset', function () {
    $query = Query::fromArray(['spa ce' => 'char acter']);

    $query->spaceCharacterEncoding(PHP_QUERY_RFC3986);

    $query->spaceCharacterEncoding(PHP_QUERY_RFC1738);

    expect($query->toString())->toBe('spa+ce=char+acter');
});

it('sets RFC3986 space character encoding via method spaceCharacterPercentTwenty', function () {
    $query = Query::fromArray(['spa ce' => 'char acter']);

    $query->spaceCharacterPercentTwenty();

    expect($query->toString())->toBe('spa%20ce=char%20acter');
});

it('sets RFC1738 space character encoding when reset via method spaceCharacterPlus', function () {
    $query = Query::fromArray(['spa ce' => 'char acter']);

    $query->spaceCharacterPercentTwenty();

    expect($query->toString())->toBe('spa%20ce=char%20acter');

    $query->spaceCharacterPlus();

    expect($query->toString())->toBe('spa+ce=char+acter');
});

it('passes on space character encoding setting to child instances', function () {
    $query = Query::fromArray(['a' => ['b' => 'foo bar']]);

    $query->spaceCharacterPercentTwenty();

    $child = $query->get('a');

    expect($child->toString())->toBe('b=foo%20bar');
});

it('updates space character encoding of child instances when switching', function () {
    $query = Query::fromArray(['a' => ['b' => 'foo bar']]);

    $child = $query->get('a');

    expect($child->toString())->toBe('b=foo+bar');

    $query->spaceCharacterPercentTwenty();

    expect($child->toString())->toBe('b=foo%20bar');
});

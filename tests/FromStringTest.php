<?php

use Crwlr\QueryString\QueryString;

it('parses a simple string with one key value pair', function () {
    expect((new QueryString('foo=bar'))->array())->toBe(['foo' => 'bar']);
});

<?php

use Crwlr\QueryString\Query;

test('Duplicate query string keys are correctly converted to arrays', function () {
    expect(Query::fromString('test=1&test2=2&test=2&test[]=3')->toArray())
        ->toEqual([
            'test' => [1, 2, 3],
            'test2' => 2,
        ]);
});

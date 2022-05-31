<?php

use Crwlr\QueryString\Query;

it('returns only arrays. All child Query instances are converted back to arrays', function () {
    $queryArray = [
        'a' => [
            'b' => '1',
            'c' => ['d' => '2', 'e' => '3'],
            'f' => ['g' => '4', 'h' => ['i']],
        ]
    ];

    $query = Query::fromArray($queryArray);

    $query->get('a')->get('c');

    $query->get('a')->last('f');

    expect($query->toArray())->toBe($queryArray);
});

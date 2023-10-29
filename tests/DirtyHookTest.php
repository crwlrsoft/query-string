<?php

use Crwlr\QueryString\Query;

it('calls the dirty hook when set() was called', function () {
    $query = Query::fromArray(['foo' => 'bar']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->set('baz', 'quz');

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when appendTo() was called', function () {
    $query = Query::fromArray(['foo' => 'bar']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->appendTo('foo', 'baz');

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when remove() was called', function () {
    $query = Query::fromArray(['foo' => 'bar']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->remove('foo');

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when removeValueFrom() was called', function () {
    $query = Query::fromArray(['foo' => ['1', '2']]);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->removeValueFrom('foo', '2');

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when filter() was called', function () {
    $query = Query::fromArray(['1', '2', '3', '4', '5']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->filter(function ($value) {
        return (int) $value > 2;
    });

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when map() was called', function () {
    $query = Query::fromArray(['1', '2', '3', '4', '5']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->map(function ($value) {
        return (int) $value + 1;
    });

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when boolToString() was called', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->boolToString();

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when boolToInt() was called', function () {
    $query = Query::fromArray(['foo' => true, 'bar' => false]);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->boolToString();

    $query->boolToInt();

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when spaceCharacterPercentTwenty() was called', function () {
    $query = Query::fromArray(['foo' => 'spa ce']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->spaceCharacterPercentTwenty();

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when spaceCharacterPlus() was called', function () {
    $query = Query::fromArray(['foo' => 'spa ce']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->spaceCharacterPercentTwenty();

    $query->spaceCharacterPlus();

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when separator() was called', function () {
    $query = Query::fromArray(['foo' => '1', 'bar' => '2']);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->separator(';');

    expect($hookWasCalled)->toBeTrue();
});

it('calls the dirty hook when something in a child Query instance was changed', function () {
    $query = Query::fromArray(['foo' => ['bar' => 'baz']]);

    $hookWasCalled = false;

    $query->setDirtyHook(function () use (&$hookWasCalled) {
        $hookWasCalled = true;
    });

    expect($hookWasCalled)->toBeFalse();

    $query->get('foo')->set('quz', 'test');

    expect($hookWasCalled)->toBeTrue();
});

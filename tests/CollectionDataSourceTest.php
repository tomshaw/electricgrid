<?php

use TomShaw\ElectricGrid\CollectionDataSource;

function collectionRows(): array
{
    return [
        ['id' => 1, 'name' => 'john', 'email' => 'a@example.com', 'author' => ['name' => 'Smith']],
        ['id' => 2, 'name' => 'jane', 'email' => 'john@example.com', 'author' => ['name' => 'Jones']],
        ['id' => 3, 'name' => 'bob', 'email' => null, 'author' => ['name' => 'Brown']],
    ];
}

it('searches collections with OR semantics across columns', function () {
    $dataSource = CollectionDataSource::make(collectionRows());
    $dataSource->search('john', ['name', 'email']);

    expect($dataSource->collection->pluck('name')->values()->all())->toBe(['john', 'jane']);
});

it('does not filter collections when the search term is empty', function () {
    $dataSource = CollectionDataSource::make(collectionRows());
    $dataSource->search('', ['name', 'email']);

    expect($dataSource->collection->count())->toBe(3);
});

it('searches nested collection values through dot notation', function () {
    $dataSource = CollectionDataSource::make(collectionRows());
    $dataSource->search('smi', ['author.name']);

    expect($dataSource->collection->pluck('name')->values()->all())->toBe(['john']);
});

it('anchors collection letter search to the start of the value', function () {
    $dataSource = CollectionDataSource::make([
        ['id' => 1, 'name' => 'Apple Pie'],
        ['id' => 2, 'name' => 'Banana Split'],
        ['id' => 3, 'name' => 'Grape A'],
    ]);
    $dataSource->searchLetter('A', ['name']);

    expect($dataSource->collection->pluck('name')->values()->all())->toBe(['Apple Pie']);
});

it('filters nested collection values from nested wire payloads', function () {
    $dataSource = CollectionDataSource::make(collectionRows());
    $dataSource->filter(['text' => ['author' => ['name' => 'jon']]]);

    expect($dataSource->collection->pluck('name')->values()->all())->toBe(['jane']);
});

it('counts the filtered collection', function () {
    $dataSource = CollectionDataSource::make(collectionRows());
    $dataSource->filter(['text' => ['name' => 'j']]);

    expect($dataSource->count())->toBe(2);
});

<?php

declare(strict_types=1);

use TomShaw\ElectricGrid\BuilderDataSource;
use TomShaw\ElectricGrid\Tests\Models\{Author, TestModel};

it('searches multiple columns with OR semantics', function () {
    TestModel::create(['name' => 'john', 'email' => 'a@example.com']);
    TestModel::create(['name' => 'jane', 'email' => 'john@example.com']);
    TestModel::create(['name' => 'bob', 'email' => 'bob@example.com']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->search('john', ['name', 'email']);

    expect($dataSource->query->pluck('name')->all())->toBe(['john', 'jane']);
});

it('keeps the search group AND-ed with existing constraints', function () {
    TestModel::create(['name' => 'john', 'status' => 1]);
    TestModel::create(['name' => 'johnny', 'status' => 0]);

    $dataSource = BuilderDataSource::make(TestModel::query()->where('status', 1));
    $dataSource->search('john', ['name', 'email']);

    expect($dataSource->query->pluck('name')->all())->toBe(['john']);
});

it('does not filter anything when the search term is empty', function () {
    TestModel::create(['name' => 'john', 'email' => null]);
    TestModel::create(['name' => 'jane', 'email' => 'jane@example.com']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->search('', ['name', 'email']);

    expect($dataSource->query->count())->toBe(2);
});

it('does not filter anything when the search term is only whitespace', function () {
    TestModel::create(['name' => 'john', 'email' => null]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->search('   ', ['name', 'email']);

    expect($dataSource->query->count())->toBe(1);
});

it('treats like wildcards in the search term as literals', function () {
    TestModel::create(['name' => '100% cotton']);
    TestModel::create(['name' => '100x cotton']);
    TestModel::create(['name' => 'a_b']);
    TestModel::create(['name' => 'axb']);

    $percent = BuilderDataSource::make(TestModel::query());
    $percent->search('100%', ['name']);
    expect($percent->query->pluck('name')->all())->toBe(['100% cotton']);

    $underscore = BuilderDataSource::make(TestModel::query());
    $underscore->search('a_b', ['name']);
    expect($underscore->query->pluck('name')->all())->toBe(['a_b']);
});

it('searches relation columns through dot notation', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->search('smi', ['author.name']);

    expect($dataSource->query->pluck('name')->all())->toBe(['first']);
});

it('combines base and relation columns in one OR group', function () {
    $smith = Author::create(['name' => 'Smith']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'smithy', 'author_id' => null]);
    TestModel::create(['name' => 'third', 'author_id' => null]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->search('smith', ['name', 'author.name']);

    expect($dataSource->query->pluck('name')->all())->toBe(['first', 'smithy']);
});

it('anchors letter search to the start of the column', function () {
    TestModel::create(['name' => 'Apple Pie']);
    TestModel::create(['name' => 'Banana Split']);
    TestModel::create(['name' => 'Grape A']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->searchLetter('A', ['name']);

    expect($dataSource->query->pluck('name')->all())->toBe(['Apple Pie']);
});

it('letter searches relation columns through dot notation', function () {
    $abbot = Author::create(['name' => 'Abbot']);
    $burns = Author::create(['name' => 'Burns']);

    TestModel::create(['name' => 'first', 'author_id' => $abbot->id]);
    TestModel::create(['name' => 'second', 'author_id' => $burns->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->searchLetter('A', ['author.name']);

    expect($dataSource->query->pluck('name')->all())->toBe(['first']);
});

it('does not filter anything when the letter is empty', function () {
    TestModel::create(['name' => 'Apple']);
    TestModel::create(['name' => 'Banana']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->searchLetter('', ['name']);

    expect($dataSource->query->count())->toBe(2);
});

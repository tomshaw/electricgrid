<?php

use TomShaw\ElectricGrid\BuilderDataSource;
use TomShaw\ElectricGrid\Exceptions\{InvalidDateFormatHandler, InvalidFilterHandler};
use TomShaw\ElectricGrid\Tests\Models\{Author, TestModel};

it('filters text on base columns', function () {
    TestModel::create(['name' => 'john']);
    TestModel::create(['name' => 'jane']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['text' => ['name' => 'joh']]);

    expect($dataSource->query->pluck('name')->all())->toBe(['john']);
});

it('ignores empty text filter values', function () {
    TestModel::create(['name' => 'john', 'email' => null]);
    TestModel::create(['name' => 'jane', 'email' => 'jane@example.com']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['text' => ['email' => '']]);

    expect($dataSource->query->count())->toBe(2);
});

it('filters text on relation columns from nested wire payloads', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['text' => ['author' => ['name' => 'smi']]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['first']);
});

it('filters text on relation columns from dotted keys', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['text' => ['author.name' => 'smi']]);

    expect($dataSource->query->pluck('name')->all())->toBe(['first']);
});

it('filters number ranges on base columns', function () {
    TestModel::create(['name' => 'one']);
    TestModel::create(['name' => 'two']);
    TestModel::create(['name' => 'three']);

    $both = BuilderDataSource::make(TestModel::query());
    $both->filter(['number' => ['id' => ['start' => 2, 'end' => 3]]]);
    expect($both->query->pluck('name')->all())->toBe(['two', 'three']);

    $startOnly = BuilderDataSource::make(TestModel::query());
    $startOnly->filter(['number' => ['id' => ['start' => 3]]]);
    expect($startOnly->query->pluck('name')->all())->toBe(['three']);

    $endOnly = BuilderDataSource::make(TestModel::query());
    $endOnly->filter(['number' => ['id' => ['end' => 1]]]);
    expect($endOnly->query->pluck('name')->all())->toBe(['one']);
});

it('ignores empty number range boundaries', function () {
    TestModel::create(['name' => 'one']);
    TestModel::create(['name' => 'two']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['number' => ['id' => ['start' => '', 'end' => '']]]);

    expect($dataSource->query->count())->toBe(2);
});

it('filters number ranges on relation columns', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['number' => ['author' => ['id' => ['start' => $jones->id]]]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['second']);
});

it('filters computed columns with having clauses', function () {
    $busy = Author::create(['name' => 'Busy']);
    $idle = Author::create(['name' => 'Idle']);

    TestModel::create(['name' => 'a', 'author_id' => $busy->id]);
    TestModel::create(['name' => 'b', 'author_id' => $busy->id]);
    TestModel::create(['name' => 'c', 'author_id' => $idle->id]);

    $dataSource = BuilderDataSource::make(Author::query()->withCount('testModels'));
    $dataSource->addComputedColumn('test_models_count');
    $dataSource->filter(['number' => ['test_models_count' => ['start' => 2]]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['Busy']);
});

it('filters select values and ignores the all sentinel', function () {
    TestModel::create(['name' => 'one', 'status' => 1]);
    TestModel::create(['name' => 'two', 'status' => 2]);

    $applied = BuilderDataSource::make(TestModel::query());
    $applied->filter(['select' => ['status' => '2']]);
    expect($applied->query->pluck('name')->all())->toBe(['two']);

    $ignored = BuilderDataSource::make(TestModel::query());
    $ignored->filter(['select' => ['status' => '-1']]);
    expect($ignored->query->count())->toBe(2);
});

it('filters select values on relation columns', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['select' => ['author' => ['name' => 'Jones']]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['second']);
});

it('filters multiselect values with whereIn and ignores the all sentinel', function () {
    TestModel::create(['name' => 'one', 'status' => 1]);
    TestModel::create(['name' => 'two', 'status' => 2]);
    TestModel::create(['name' => 'three', 'status' => 3]);

    $applied = BuilderDataSource::make(TestModel::query());
    $applied->filter(['multiselect' => ['status' => ['1', '3']]]);
    expect($applied->query->pluck('name')->all())->toBe(['one', 'three']);

    $ignored = BuilderDataSource::make(TestModel::query());
    $ignored->filter(['multiselect' => ['status' => ['-1']]]);
    expect($ignored->query->count())->toBe(3);
});

it('filters multiselect values on relation columns', function () {
    $smith = Author::create(['name' => 'Smith']);
    $jones = Author::create(['name' => 'Jones']);
    $brown = Author::create(['name' => 'Brown']);

    TestModel::create(['name' => 'first', 'author_id' => $smith->id]);
    TestModel::create(['name' => 'second', 'author_id' => $jones->id]);
    TestModel::create(['name' => 'third', 'author_id' => $brown->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['multiselect' => ['author' => ['name' => ['Smith', 'Brown']]]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['first', 'third']);
});

it('filters boolean values and ignores the all sentinel', function () {
    TestModel::create(['name' => 'paid', 'invoiced' => true]);
    TestModel::create(['name' => 'unpaid', 'invoiced' => false]);

    $truthy = BuilderDataSource::make(TestModel::query());
    $truthy->filter(['boolean' => ['invoiced' => 'true']]);
    expect($truthy->query->pluck('name')->all())->toBe(['paid']);

    $falsy = BuilderDataSource::make(TestModel::query());
    $falsy->filter(['boolean' => ['invoiced' => 'false']]);
    expect($falsy->query->pluck('name')->all())->toBe(['unpaid']);

    $ignored = BuilderDataSource::make(TestModel::query());
    $ignored->filter(['boolean' => ['invoiced' => '-1']]);
    expect($ignored->query->count())->toBe(2);
});

it('filters date ranges on base columns', function () {
    TestModel::create(['name' => 'early', 'created_at' => '2024-01-01 10:00:00']);
    TestModel::create(['name' => 'late', 'created_at' => '2024-03-01 10:00:00']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['datepicker' => ['created_at' => ['start' => '2024-02-01']]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['late']);
});

it('filters date ranges on relation columns', function () {
    $old = Author::create(['name' => 'Old', 'created_at' => '2024-01-01 10:00:00']);
    $new = Author::create(['name' => 'New', 'created_at' => '2024-03-01 10:00:00']);

    TestModel::create(['name' => 'first', 'author_id' => $old->id]);
    TestModel::create(['name' => 'second', 'author_id' => $new->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['datepicker' => ['author' => ['created_at' => ['start' => '2024-02-01']]]]);

    expect($dataSource->query->pluck('name')->all())->toBe(['second']);
});

it('skips cleared date picker values without throwing', function () {
    TestModel::create(['name' => 'one']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['datepicker' => ['created_at' => ['start' => '', 'end' => null]]]);

    expect($dataSource->query->count())->toBe(1);
});

it('throws on malformed date values', function () {
    $dataSource = BuilderDataSource::make(TestModel::query());

    $dataSource->filter(['datepicker' => ['created_at' => ['start' => 'not-a-date']]]);
})->throws(InvalidDateFormatHandler::class);

it('throws on unknown filter types', function () {
    $dataSource = BuilderDataSource::make(TestModel::query());

    $dataSource->filter(['bogus' => ['name' => 'x']]);
})->throws(InvalidFilterHandler::class);

it('silently ignores legacy search keys from persisted sessions', function () {
    TestModel::create(['name' => 'one']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->filter(['search_term' => ['name' => 'zzz'], 'search_letter' => ['name' => 'Z']]);

    expect($dataSource->query->count())->toBe(1);
});

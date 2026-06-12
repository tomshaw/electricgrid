<?php

use TomShaw\ElectricGrid\{BuilderDataSource, SortDirection};
use TomShaw\ElectricGrid\Exceptions\InvalidModelRelationsHandler;
use TomShaw\ElectricGrid\Tests\Models\{Author, Tag, TestModel};

it('sorts base columns with a qualified column name', function () {
    TestModel::create(['name' => 'bravo']);
    TestModel::create(['name' => 'alpha']);
    TestModel::create(['name' => 'charlie']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->orderBy('name', SortDirection::Desc);

    expect($dataSource->query->pluck('name')->all())->toBe(['charlie', 'bravo', 'alpha']);
});

it('accepts string sort directions case-insensitively', function () {
    TestModel::create(['name' => 'bravo']);
    TestModel::create(['name' => 'alpha']);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->orderBy('name', 'desc');

    expect($dataSource->query->pluck('name')->all())->toBe(['bravo', 'alpha']);
});

it('sorts by a belongsTo relation column without requiring eager loads', function () {
    $zed = Author::create(['name' => 'Zed']);
    $ann = Author::create(['name' => 'Ann']);
    $mia = Author::create(['name' => 'Mia']);

    TestModel::create(['name' => 'one', 'author_id' => $zed->id]);
    TestModel::create(['name' => 'two', 'author_id' => $ann->id]);
    TestModel::create(['name' => 'three', 'author_id' => $mia->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->orderBy('author.name', SortDirection::Asc);

    expect($dataSource->query->pluck('name')->all())->toBe(['two', 'three', 'one']);
});

it('does not drop rows with a null relation when sorting by it', function () {
    $ann = Author::create(['name' => 'Ann']);

    TestModel::create(['name' => 'one', 'author_id' => $ann->id]);
    TestModel::create(['name' => 'orphan', 'author_id' => null]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->orderBy('author.name', SortDirection::Asc);

    expect($dataSource->query->count())->toBe(2);
});

it('does not duplicate parent rows when sorting by a hasMany relation column', function () {
    $first = Author::create(['name' => 'First']);
    $second = Author::create(['name' => 'Second']);

    TestModel::create(['name' => 'b', 'author_id' => $first->id]);
    TestModel::create(['name' => 'z', 'author_id' => $first->id]);
    TestModel::create(['name' => 'a', 'author_id' => $second->id]);

    $ascending = BuilderDataSource::make(Author::query());
    $ascending->orderBy('testModels.name', SortDirection::Asc);
    expect($ascending->query->pluck('name')->all())->toBe(['Second', 'First']);

    $descending = BuilderDataSource::make(Author::query());
    $descending->orderBy('testModels.name', SortDirection::Desc);
    expect($descending->query->pluck('name')->all())->toBe(['First', 'Second']);
});

it('does not inflate pagination totals when sorting by a relation column', function () {
    $first = Author::create(['name' => 'First']);

    TestModel::create(['name' => 'b', 'author_id' => $first->id]);
    TestModel::create(['name' => 'z', 'author_id' => $first->id]);

    $dataSource = BuilderDataSource::make(Author::query());
    $dataSource->orderBy('testModels.name', SortDirection::Asc);

    expect($dataSource->paginate(10)->total())->toBe(1);
});

it('does not duplicate rows when sorting by a belongsToMany relation column', function () {
    $x = Tag::create(['name' => 'x']);
    $y = Tag::create(['name' => 'y']);
    $a = Tag::create(['name' => 'a']);

    $one = TestModel::create(['name' => 'one']);
    $one->tags()->attach([$x->id, $y->id]);

    $two = TestModel::create(['name' => 'two']);
    $two->tags()->attach([$a->id]);

    $dataSource = BuilderDataSource::make(TestModel::query());
    $dataSource->orderBy('tags.name', SortDirection::Asc);

    expect($dataSource->query->pluck('name')->all())->toBe(['two', 'one']);
});

it('sorts computed columns without qualifying them', function () {
    $one = TestModel::create(['name' => 'one']);
    $two = TestModel::create(['name' => 'two']);

    $tagA = Tag::create(['name' => 'a']);
    $tagB = Tag::create(['name' => 'b']);

    $two->tags()->attach([$tagA->id, $tagB->id]);
    $one->tags()->attach([$tagA->id]);

    $dataSource = BuilderDataSource::make(TestModel::query()->withCount('tags'));
    $dataSource->addComputedColumn('tags_count');
    $dataSource->orderBy('tags_count', SortDirection::Desc);

    expect($dataSource->query->pluck('name')->all())->toBe(['two', 'one']);
});

it('throws when sorting through nested relations', function () {
    $dataSource = BuilderDataSource::make(TestModel::query());

    $dataSource->orderBy('author.profile.name', SortDirection::Asc);
})->throws(InvalidModelRelationsHandler::class);

it('throws when sorting by an undefined relation', function () {
    $dataSource = BuilderDataSource::make(TestModel::query());

    $dataSource->orderBy('missing.name', SortDirection::Asc);
})->throws(InvalidModelRelationsHandler::class);

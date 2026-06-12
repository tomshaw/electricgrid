<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');

    TestModel::create(['name' => 'Alpha', 'status' => 1, 'invoiced' => true]);
    TestModel::create(['name' => 'Beta', 'status' => 2, 'invoiced' => false]);
    TestModel::create(['name' => 'Gamma', 'status' => 1, 'invoiced' => true]);
});

/**
 * Reads the written export back into an array of rows.
 *
 * @return array<int, array<int, mixed>>
 */
function exportRows(BinaryFileResponse $response): array
{
    return IOFactory::load($response->getFile()->getPathname())
        ->getActiveSheet()
        ->toArray();
}

/**
 * @return array<int, mixed>
 */
function exportColumn(BinaryFileResponse $response, int $column): array
{
    $rows = array_slice(exportRows($response), 1);

    return array_column($rows, $column);
}

it('returns a downloadable spreadsheet response', function () {
    $response = Livewire::test(TestComponent::class)
        ->set('selectedAction', 'export-xlsx')
        ->instance()
        ->handleSelectedAction();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    expect($response->headers->get('Content-Disposition'))->toContain('export.xlsx');
});

it('exports the entire filtered dataset when no rows are selected', function () {
    $response = Livewire::test(TestComponent::class)
        ->set('selectedAction', 'export-xlsx')
        ->instance()
        ->handleSelectedAction();

    $rows = exportRows($response);

    expect($rows[0])->toBe(['ID', 'Name', 'Status', 'Invoiced']);
    expect($rows)->toHaveCount(4);
    expect(exportColumn($response, 1))->toEqualCanonicalizing(['Alpha', 'Beta', 'Gamma']);
});

it('respects active filters when exporting with no rows selected', function () {
    $response = Livewire::test(TestComponent::class)
        ->set('filter', ['boolean' => ['invoiced' => 'true']])
        ->set('selectedAction', 'export-xlsx')
        ->instance()
        ->handleSelectedAction();

    expect(exportRows($response))->toHaveCount(3);
    expect(exportColumn($response, 1))->toEqualCanonicalizing(['Alpha', 'Gamma']);
});

it('exports only selected rows when checkboxes are populated', function () {
    $first = TestModel::query()->orderBy('id')->first();

    $response = Livewire::test(TestComponent::class)
        ->set('checkboxValues', [$first->id])
        ->set('selectedAction', 'export-xlsx')
        ->instance()
        ->handleSelectedAction();

    $rows = exportRows($response);

    expect($rows)->toHaveCount(2);
    expect($rows[1][1])->toBe($first->name);
});

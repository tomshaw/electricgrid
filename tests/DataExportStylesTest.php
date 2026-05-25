<?php

namespace TomShaw\ElectricGrid\Tests;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use TomShaw\ElectricGrid\DataExport;

function styledSheet(): Worksheet
{
    $export = (new DataExport(collect([
        (object) ['c1' => 'r1a', 'c2' => 'r1b', 'c3' => 'r1c'],
        (object) ['c1' => 'r2a', 'c2' => 'r2b', 'c3' => 'r2c'],
    ])))
        ->setHeadings(['Col A', 'Col B', 'Col C'])
        ->setFileName('styled.xlsx')
        ->setStyles([
            '1' => ['font' => ['bold' => true]],
            'B2' => ['font' => ['italic' => true]],
            'C' => ['font' => ['size' => 16]],
        ])
        ->setColumnWidths(['A' => 20]);

    return IOFactory::load($export->download()->getFile()->getPathname())->getActiveSheet();
}

it('applies a whole-row style from a numeric key', function () {
    expect(styledSheet()->getStyle('A1')->getFont()->getBold())->toBeTrue();
});

it('applies a single-cell style from a cell-reference key', function () {
    expect(styledSheet()->getStyle('B2')->getFont()->getItalic())->toBeTrue();
});

it('applies a whole-column style from a column-letter key', function () {
    expect(styledSheet()->getStyle('C1')->getFont()->getSize())->toEqual(16.0);
});

it('applies explicit column widths', function () {
    expect(styledSheet()->getColumnDimension('A')->getWidth())->toEqual(20.0);
});

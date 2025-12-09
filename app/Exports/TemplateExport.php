<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class TemplateExport implements FromCollection, WithHeadings
{
    protected $headers;

    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public function collection()
    {
        return new Collection([]); // Data kosong
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
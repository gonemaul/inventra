<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

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

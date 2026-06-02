<?php

namespace App\Exports;

use App\Models\ClusteringRun;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ClusteringRunExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(private readonly ClusteringRun $run) {}

    public function title(): string
    {
        return 'Penugasan Cluster';
    }

    public function headings(): array
    {
        return [
            'Nomor Laporan',
            'Tanggal Kejadian',
            'Jenis Kejahatan',
            'Modus',
            'Provinsi',
            'Kerugian (IDR)',
            'Jumlah Korban',
            'Tingkat Keparahan',
            'Cluster',
            'PCA X',
            'PCA Y',
        ];
    }

    public function array(): array
    {
        return $this->run->assignments()
            ->with('record')
            ->orderBy('cluster')
            ->get()
            ->map(fn ($a) => [
                $a->record?->nomor_laporan,
                $a->record?->tanggal_kejadian?->format('Y-m-d'),
                $a->record?->jenis_kejahatan,
                $a->record?->modus_operandi,
                $a->record?->provinsi,
                $a->record?->estimasi_kerugian,
                $a->record?->jumlah_korban,
                $a->record?->tingkat_keparahan,
                $a->cluster,
                $a->pca_x,
                $a->pca_y,
            ])
            ->toArray();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1:K1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1F2937');
                $sheet->getStyle('A1:K1')->getFont()->getColor()->setRGB('FFFFFF');
            },
        ];
    }
}

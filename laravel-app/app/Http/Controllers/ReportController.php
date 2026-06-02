<?php

namespace App\Http\Controllers;

use App\Exports\ClusteringRunExport;
use App\Models\ClusteringRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function exportPdf(ClusteringRun $run): Response
    {
        abort_unless($run->status === ClusteringRun::STATUS_SUKSES, 404, 'Hasil analisis tidak tersedia.');

        $pdf = Pdf::loadView('reports.pdf', [
            'run' => $run,
            'profiles' => $run->profiles,
            'importance' => $run->feature_importance,
            'tanggal' => now()->translatedFormat('d F Y'),
        ])->setPaper('A4', 'portrait');

        $filename = sprintf('SIANCEK-laporan-%s-%s.pdf', $run->id, now()->format('Ymd'));
        return $pdf->download($filename);
    }

    public function exportExcel(ClusteringRun $run): BinaryFileResponse
    {
        abort_unless($run->status === ClusteringRun::STATUS_SUKSES, 404, 'Hasil analisis tidak tersedia.');

        $filename = sprintf('SIANCEK-data-%s-%s.xlsx', $run->id, now()->format('Ymd'));
        return Excel::download(new ClusteringRunExport($run), $filename);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ClusteringRun;
use App\Models\CybercrimeRecord;
use App\Services\ClusteringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function __construct(private readonly ClusteringService $clustering) {}

    public function index(): View
    {
        $runs = ClusteringRun::with('creator')->latest()->paginate(15);
        return view('analysis.index', compact('runs'));
    }

    public function create(): View
    {
        $jenisList = CybercrimeRecord::query()->select('jenis_kejahatan')->distinct()->orderBy('jenis_kejahatan')->pluck('jenis_kejahatan');
        $provinsiList = CybercrimeRecord::query()->select('provinsi')->distinct()->orderBy('provinsi')->pluck('provinsi');
        $allowedNumeric = ClusteringService::ALLOWED_NUMERIC;
        $allowedCategorical = ClusteringService::ALLOWED_CATEGORICAL;
        $totalData = CybercrimeRecord::count();

        return view('analysis.create', compact('jenisList', 'provinsiList', 'allowedNumeric', 'allowedCategorical', 'totalData'));
    }

    /**
     * Endpoint AJAX ringan: hitung berapa baris yang cocok dengan filter saat ini,
     * agar pengguna tahu apakah subset cukup besar SEBELUM menjalankan Elbow.
     */
    public function previewCount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'k_min' => ['nullable', 'integer', 'min:2', 'max:20'],
            'filter' => ['array'],
            'filter.tanggal_mulai' => ['nullable', 'date'],
            'filter.tanggal_selesai' => ['nullable', 'date'],
            'filter.jenis_kejahatan' => ['array'],
            'filter.provinsi' => ['array'],
        ]);

        $count = $this->clustering->countDataset($data['filter'] ?? []);
        $minRequired = ClusteringService::minRequiredRows($data['k_min'] ?? 2);

        return response()->json([
            'count' => $count,
            'min_required' => $minRequired,
            'sufficient' => $count >= $minRequired,
        ]);
    }

    /**
     * Endpoint AJAX untuk preview Elbow sebelum user memilih K final.
     */
    public function elbow(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fitur_numerik' => ['array'],
            'fitur_numerik.*' => ['string'],
            'fitur_kategorikal' => ['array'],
            'fitur_kategorikal.*' => ['string'],
            'scaler' => ['nullable', 'in:standard,minmax,robust'],
            'k_min' => ['nullable', 'integer', 'min:2', 'max:20'],
            'k_max' => ['nullable', 'integer', 'min:2', 'max:20'],
            'filter' => ['array'],
            'filter.tanggal_mulai' => ['nullable', 'date'],
            'filter.tanggal_selesai' => ['nullable', 'date'],
            'filter.jenis_kejahatan' => ['array'],
            'filter.provinsi' => ['array'],
        ]);

        try {
            $result = $this->clustering->elbow(
                filter: $data['filter'] ?? [],
                numericFeatures: $data['fitur_numerik'] ?? [],
                categoricalFeatures: $data['fitur_kategorikal'] ?? [],
                kMin: $data['k_min'] ?? 2,
                kMax: $data['k_max'] ?? 10,
                scaler: $data['scaler'] ?? 'standard',
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:160'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'n_clusters' => ['required', 'integer', 'min:2', 'max:20'],
            'random_state' => ['nullable', 'integer', 'min:0'],
            'scaler' => ['required', 'in:standard,minmax,robust'],
            'mode' => ['required', 'in:manual,auto'],
            'fitur_numerik' => ['array'],
            'fitur_numerik.*' => ['string'],
            'fitur_kategorikal' => ['array'],
            'fitur_kategorikal.*' => ['string'],
            'filter' => ['array'],
            'filter.tanggal_mulai' => ['nullable', 'date'],
            'filter.tanggal_selesai' => ['nullable', 'date'],
            'filter.jenis_kejahatan' => ['array'],
            'filter.provinsi' => ['array'],
        ]);

        try {
            $run = $this->clustering->runAndPersist([
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'n_clusters' => $data['n_clusters'],
                'random_state' => $data['random_state'] ?? 42,
                'scaler' => $data['scaler'],
                'mode' => $data['mode'],
                'fitur_numerik' => $data['fitur_numerik'] ?? [],
                'fitur_kategorikal' => $data['fitur_kategorikal'] ?? [],
                'filter' => $data['filter'] ?? [],
            ], createdBy: $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['analysis' => $e->getMessage()]);
        }

        return redirect()->route('analysis.show', $run)->with('status', 'Analisis berhasil dijalankan.');
    }

    public function show(ClusteringRun $run): View
    {
        if ($run->status !== ClusteringRun::STATUS_SUKSES) {
            return view('analysis.show', ['run' => $run, 'profiles' => [], 'projection' => [], 'importance' => [], 'distribution' => []]);
        }
        $profiles = $run->profiles;
        $projection = $run->projection;
        $importance = $run->feature_importance;

        $distribution = $run->assignments()
            ->selectRaw('cluster, COUNT(*) as total')
            ->groupBy('cluster')
            ->orderBy('cluster')
            ->pluck('total', 'cluster')
            ->all();

        return view('analysis.show', compact('run', 'profiles', 'projection', 'importance', 'distribution'));
    }

    public function destroy(ClusteringRun $run): RedirectResponse
    {
        $run->delete();
        return redirect()->route('analysis.index')->with('status', 'Analisis dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Imports\CybercrimeImport;
use App\Models\ActivityLog;
use App\Models\CybercrimeRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CybercrimeController extends Controller
{
    public function index(Request $request): View
    {
        $q = CybercrimeRecord::query()->with('inputBy:id,name');

        if ($search = $request->string('q')->toString()) {
            $q->where(function ($w) use ($search) {
                $w->where('nomor_laporan', 'like', "%$search%")
                  ->orWhere('jenis_kejahatan', 'like', "%$search%")
                  ->orWhere('provinsi', 'like', "%$search%")
                  ->orWhere('modus_operandi', 'like', "%$search%");
            });
        }
        if ($jenis = $request->string('jenis')->toString()) {
            $q->where('jenis_kejahatan', $jenis);
        }
        if ($provinsi = $request->string('provinsi')->toString()) {
            $q->where('provinsi', $provinsi);
        }
        if ($mulai = $request->date('mulai')) {
            $q->whereDate('tanggal_kejadian', '>=', $mulai);
        }
        if ($selesai = $request->date('selesai')) {
            $q->whereDate('tanggal_kejadian', '<=', $selesai);
        }

        $records = $q->latest('tanggal_kejadian')->paginate(20)->withQueryString();

        $jenisList = CybercrimeRecord::query()->select('jenis_kejahatan')->distinct()->orderBy('jenis_kejahatan')->pluck('jenis_kejahatan');
        $provinsiList = CybercrimeRecord::query()->select('provinsi')->distinct()->orderBy('provinsi')->pluck('provinsi');

        return view('cybercrime.index', compact('records', 'jenisList', 'provinsiList'));
    }

    public function create(): View
    {
        return view('cybercrime.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['input_by'] = $request->user()->id;
        $record = CybercrimeRecord::create($data);
        ActivityLog::record('cybercrime.create', $record);
        return redirect()->route('cybercrime.index')->with('status', 'Data laporan berhasil ditambahkan.');
    }

    public function edit(CybercrimeRecord $cybercrime): View
    {
        return view('cybercrime.edit', ['record' => $cybercrime]);
    }

    public function update(Request $request, CybercrimeRecord $cybercrime): RedirectResponse
    {
        $data = $this->validatePayload($request, $cybercrime->id);
        $cybercrime->update($data);
        ActivityLog::record('cybercrime.update', $cybercrime);
        return redirect()->route('cybercrime.index')->with('status', 'Data laporan diperbarui.');
    }

    public function destroy(CybercrimeRecord $cybercrime): RedirectResponse
    {
        $cybercrime->delete();
        ActivityLog::record('cybercrime.delete', $cybercrime);
        return back()->with('status', 'Data laporan dihapus.');
    }

    public function importForm(): View
    {
        return view('cybercrime.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $importer = new CybercrimeImport($request->user()->id);
        Excel::import($importer, $request->file('file'));

        ActivityLog::record('cybercrime.import', null, [
            'imported' => $importer->getImportedCount(),
            'failed' => count($importer->getErrors()),
        ]);

        return redirect()->route('cybercrime.index')->with('status',
            "Import selesai. {$importer->getImportedCount()} baris berhasil; "
            .count($importer->getErrors())." baris gagal."
        );
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:cybercrime_records,nomor_laporan'.($ignoreId ? ",$ignoreId" : '');
        return $request->validate([
            'nomor_laporan' => ['required', 'string', 'max:64', $uniqueRule],
            'tanggal_kejadian' => ['required', 'date'],
            'tanggal_laporan' => ['required', 'date', 'after_or_equal:tanggal_kejadian'],
            'jenis_kejahatan' => ['required', 'string', 'max:80'],
            'sub_jenis' => ['nullable', 'string', 'max:120'],
            'modus_operandi' => ['required', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:60'],
            'provinsi' => ['required', 'string', 'max:64'],
            'kota_kabupaten' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'usia_korban' => ['nullable', 'integer', 'between:1,120'],
            'jenis_kelamin_korban' => ['required', 'in:L,P,TD'],
            'pekerjaan_korban' => ['nullable', 'string', 'max:80'],
            'pendidikan_korban' => ['required', 'in:SD,SMP,SMA,D3,S1,S2,S3,TD'],
            'estimasi_kerugian' => ['required', 'integer', 'min:0'],
            'jumlah_korban' => ['required', 'integer', 'min:1'],
            'tingkat_keparahan' => ['required', 'in:rendah,sedang,tinggi,kritis'],
            'status_kasus' => ['required', 'in:baru,dalam_penyelidikan,p21,selesai,dihentikan'],
            'tersangka_teridentifikasi' => ['required', 'boolean'],
            'sumber_data' => ['required', 'string', 'max:80'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}

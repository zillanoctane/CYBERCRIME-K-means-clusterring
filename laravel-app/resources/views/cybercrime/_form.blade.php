@php
    $r = $record ?? null;
    $val = function ($field, $default = null) use ($r) {
        $raw = $r?->{$field};
        if ($raw instanceof \Carbon\CarbonInterface) {
            $raw = $raw->toDateString();
        }
        return old($field, $raw ?? $default);
    };
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Nomor Laporan <span class="text-rose-500">*</span></label>
        <input type="text" name="nomor_laporan" value="{{ $val('nomor_laporan') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" placeholder="LP/YYYY/0001/SBR" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Tanggal Kejadian <span class="text-rose-500">*</span></label>
        <input type="date" name="tanggal_kejadian" value="{{ $val('tanggal_kejadian') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Tanggal Laporan <span class="text-rose-500">*</span></label>
        <input type="date" name="tanggal_laporan" value="{{ $val('tanggal_laporan') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Jenis Kejahatan <span class="text-rose-500">*</span></label>
        <input type="text" name="jenis_kejahatan" value="{{ $val('jenis_kejahatan') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Penipuan Online" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Sub Jenis</label>
        <input type="text" name="sub_jenis" value="{{ $val('sub_jenis') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Modus Operandi <span class="text-rose-500">*</span></label>
        <input type="text" name="modus_operandi" value="{{ $val('modus_operandi') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Platform</label>
        <input type="text" name="platform" value="{{ $val('platform') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="WhatsApp, Instagram, dst." />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Provinsi <span class="text-rose-500">*</span></label>
        <input type="text" name="provinsi" value="{{ $val('provinsi') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Kota/Kabupaten</label>
        <input type="text" name="kota_kabupaten" value="{{ $val('kota_kabupaten') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Usia Korban</label>
        <input type="number" name="usia_korban" min="1" max="120" value="{{ $val('usia_korban') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Kelamin</label>
        <select name="jenis_kelamin_korban" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
            @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan', 'TD' => 'Tidak Diketahui'] as $k => $v)
                <option value="{{ $k }}" @selected($val('jenis_kelamin_korban') === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Pendidikan</label>
        <select name="pendidikan_korban" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
            @foreach (['SD','SMP','SMA','D3','S1','S2','S3','TD'] as $opt)
                <option value="{{ $opt }}" @selected($val('pendidikan_korban') === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Pekerjaan</label>
        <input type="text" name="pekerjaan_korban" value="{{ $val('pekerjaan_korban') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Estimasi Kerugian (IDR) <span class="text-rose-500">*</span></label>
        <input type="number" name="estimasi_kerugian" min="0" required value="{{ $val('estimasi_kerugian', 0) }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Jumlah Korban <span class="text-rose-500">*</span></label>
        <input type="number" name="jumlah_korban" min="1" required value="{{ $val('jumlah_korban', 1) }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Tingkat Keparahan <span class="text-rose-500">*</span></label>
        <select name="tingkat_keparahan" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
            @foreach (['rendah','sedang','tinggi','kritis'] as $opt)
                <option value="{{ $opt }}" @selected($val('tingkat_keparahan', 'sedang') === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Status Kasus <span class="text-rose-500">*</span></label>
        <select name="status_kasus" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
            @foreach (['baru','dalam_penyelidikan','p21','selesai','dihentikan'] as $opt)
                <option value="{{ $opt }}" @selected($val('status_kasus', 'baru') === $opt)>{{ str_replace('_',' ', ucfirst($opt)) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="text-sm font-medium text-slate-700">Tersangka Teridentifikasi</label>
        <select name="tersangka_teridentifikasi" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">
            <option value="0" @selected(!$val('tersangka_teridentifikasi'))>Belum</option>
            <option value="1" @selected($val('tersangka_teridentifikasi'))>Sudah</option>
        </select>
    </div>
    <div>
        <label class="text-sm font-medium text-slate-700">Sumber Data <span class="text-rose-500">*</span></label>
        <input type="text" name="sumber_data" value="{{ $val('sumber_data', 'Laporan Masyarakat') }}" required class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm" />
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div>
            <label class="text-sm font-medium text-slate-700">Latitude</label>
            <input type="number" step="any" name="latitude" value="{{ $val('latitude') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Longitude</label>
            <input type="number" step="any" name="longitude" value="{{ $val('longitude') }}" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm font-mono" />
        </div>
    </div>
</div>

<div class="mt-4">
    <label class="text-sm font-medium text-slate-700">Keterangan</label>
    <textarea name="keterangan" rows="3" class="mt-1 w-full rounded-lg border-slate-200 focus:border-brand-500 focus:ring-brand-500 text-sm">{{ $val('keterangan') }}</textarea>
</div>

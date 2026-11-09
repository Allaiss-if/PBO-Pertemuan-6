<?php

declare(strict_types=1);
require_once __DIR__ . '/DataMahasiswa.php';
session_start();

function aman(mixed $teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$repo = new DataMahasiswa($_SESSION['mahasiswa'] ?? []);
$pesan = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$error = '';
$keys = [
    'nim' => 'NIM',
    'nama' => 'Nama',
    'fakultas' => 'Fakultas',
    'prodi' => 'Program Studi',
    'asalSekolah' => 'Asal Sekolah',
    'semester' => 'Semester',
    'hobi' => 'Hobi',
    'agama' => 'Agama'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
            throw new InvalidArgumentException('Token formulir tidak valid. Muat ulang halaman.');
        }
        $aksi = (string)($_POST['aksi'] ?? '');
        $nimLama = (string)($_POST['nim_lama'] ?? '');
        if ($aksi === 'hapus') {
            $nim = trim((string)($_POST['nim'] ?? ''));
            if (!$repo->hapus($nim)) throw new InvalidArgumentException('NIM tidak ditemukan.');
        } elseif (in_array($aksi, ['tambah', 'ubah'], true)) {
            $data = [];
            foreach ($keys as $k => $label) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }
            foreach (['nim', 'nama', 'fakultas', 'prodi', 'asalSekolah', 'agama'] as $k) {
                if ($data[$k] === '') throw new InvalidArgumentException($keys[$k] . ' wajib diisi.');
            }
            // Input semester divalidasi server-side, bukan sekadar casting.
            $semester = filter_var($data['semester'], FILTER_VALIDATE_INT);
            if ($semester === false || $semester < 1 || $semester > 14) {
                throw new InvalidArgumentException('Semester harus 1–14.');
            }
            // Pada mode ubah, NIM diambil dari field tersembunyi (NIM readonly di form).
            $nim = $aksi === 'ubah' ? $nimLama : $data['nim'];
            // Jenis, UKT, dan potongan divalidasi dengan FILTER_VALIDATE_INT;
            // aturan nilai (negatif / di luar 0-100) ditegakkan oleh constructor child.
            $jenis = (string)($_POST['jenis'] ?? '');
            $uktRaw = trim((string)($_POST['ukt'] ?? ''));
            if ($uktRaw === '') {
                throw new InvalidArgumentException('UKT wajib diisi.');
            }
            $ukt = filter_var($uktRaw, FILTER_VALIDATE_INT);
            if ($ukt === false) {
                throw new InvalidArgumentException('UKT harus bilangan bulat.');
            }
            $induk = [
                $nim,
                $data['nama'],
                $data['fakultas'],
                $data['prodi'],
                $data['asalSekolah'],
                $semester,
                $data['hobi'],
                $data['agama'],
            ];
            if ($aksi === 'tambah' && $repo->cari($nim) !== null) {
                throw new InvalidArgumentException('NIM duplikat.');
            }
            if ($jenis === 'reguler') {
                // CONSTRUCTOR child dipanggil; validasi atribut induk ada di Mahasiswa.
                $m = new MahasiswaReguler(...[...$induk, $ukt]);
            } elseif ($jenis === 'beasiswa') {
                $potRaw = trim((string)($_POST['potongan'] ?? ''));
                if ($potRaw === '') {
                    throw new InvalidArgumentException('Potongan wajib diisi untuk mahasiswa beasiswa.');
                }
                $potongan = filter_var($potRaw, FILTER_VALIDATE_INT);
                if ($potongan === false) {
                    throw new InvalidArgumentException('Potongan harus bilangan bulat.');
                }
                $m = new MahasiswaBeasiswa(...[...$induk, $ukt, $potongan]);
            } else {
                throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal.');
            }
            // CRUD bekerja pada object Mahasiswa; akses hanya lewat getter.
            $berhasil = $aksi === 'tambah'
                ? $repo->tambah($m)
                : $repo->ubah($nimLama, $m);
            if (!$berhasil) throw new InvalidArgumentException('NIM duplikat atau data lama tidak ditemukan.');
        } else {
            throw new InvalidArgumentException('Aksi tidak dikenal.');
        }
        $_SESSION['mahasiswa'] = $repo->semua();
        $_SESSION['flash'] = 'Data berhasil ' . ($aksi === 'hapus' ? 'dihapus' : ($aksi === 'ubah' ? 'diubah' : 'ditambahkan')) . '.';
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$edit = $repo->cari((string)($_GET['edit'] ?? ''));

// Fitur cari: filter berdasarkan NIM atau nama, tidak mengubah data di session.
$q = trim((string)($_GET['q'] ?? ''));
$daftar = $repo->semua();
if ($q !== '') {
    $qLower = strtolower($q); // strtolower cukup: NIM dan nama 
    $daftar = array_filter(
        $daftar,
        static fn(Mahasiswa $m): bool =>
        str_contains(strtolower($m->getNim()), $qLower)
            || str_contains(strtolower($m->getNama()), $qLower)
    );
}

// Nilai form: dibaca melalui GETTER, bukan property langsung.
function nilaiEdit(?Mahasiswa $m, string $key): string
{
    if ($m === null) return '';
    return match ($key) {
        'nim' => $m->getNim(),
        'nama' => $m->getNama(),
        'fakultas' => $m->getFakultas(),
        'prodi' => $m->getProdi(),
        'asalSekolah' => $m->getAsalSekolah(),
        'semester' => (string)$m->getSemester(),
        'hobi' => $m->getHobi(),
        'agama' => $m->getAgama(),
        default => '',
    };
}

// Nilai jenis/UKT/potongan untuk form edit, dibaca lewat getter child (bukan property private).
function nilaiTambahan(?Mahasiswa $m): array
{
    if ($m instanceof MahasiswaBeasiswa) {
        return ['beasiswa', (string)$m->getUkt(), (string)$m->getPotonganPersen()];
    }
    if ($m instanceof MahasiswaReguler) {
        return ['reguler', (string)$m->getUkt(), ''];
    }
    return ['', '', ''];
}
[$jenisEdit, $uktEdit, $potonganEdit] = nilaiTambahan($edit);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Praktikum PBO 6 - CRUD Mahasiswa (Inheritance)</title>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            font-family: system-ui, Arial, sans-serif;
            background: #f1f5f9;
            color: #15324d;
            margin: 0
        }

        main {
            max-width: 1120px;
            margin: 36px auto;
            padding: 0 18px
        }

        h1 {
            margin-bottom: 4px
        }

        small,
        .muted {
            color: #60758a
        }

        .panel {
            background: white;
            border-radius: 13px;
            padding: 24px;
            margin: 20px 0;
            box-shadow: 0 5px 18px #16324d14
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px
        }

        label {
            font-weight: 650;
            font-size: 13px;
            display: block
        }

        input,
        select {
            display: block;
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #c8d5e3;
            border-radius: 7px;
            background: white;
            font: inherit
        }

        input[readonly] {
            background: #eaf0f5;
            color: #60758a
        }

        button,
        .btn {
            border: 0;
            border-radius: 7px;
            background: #176397;
            color: white;
            padding: 10px 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px
        }

        .danger {
            background: #b9414a
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center
        }

        .actions form {
            margin: 0
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px
        }

        th,
        td {
            padding: 12px 9px;
            text-align: left;
            border-bottom: 1px solid #e1e8f0
        }

        th {
            background: #e8f1f8
        }

        .scroll {
            overflow-x: auto
        }

        .notice {
            padding: 12px;
            border-radius: 8px;
            background: #e3f4e9
        }

        .err {
            background: #ffe8e8
        }

        .cari {
            display: flex;
            gap: 7px;
            margin: 14px 0
        }

        .cari input {
            margin-top: 0;
            max-width: 320px
        }

        @media(max-width:650px) {
            .grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <main>

        <h1>Data Mahasiswa</h1>
        <p class="muted">PBO Pertemuan 6: Inheritance - pewarisan class, abstract, overriding, CRUD Array/Session</p>
        <?php if ($pesan): ?><div class="notice"><?= aman($pesan) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="notice err"><?= aman($error) ?></div><?php endif; ?>
        <section class="panel">
            <h2><?= $edit ? 'Edit' : 'Tambah' ?> Data Mahasiswa</h2>

            <form method="post"><input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
                <input type="hidden" name="nim_lama" value="<?= aman($edit?->getNim() ?? '') ?>">
                <div class="grid">

                    <?php foreach ($keys as $key => $label):
                        // Nilai form diambil lewat getter (nilaiEdit), bukan $edit->$key.
                        $value = nilaiEdit($edit, $key);
                    ?>
                        <label><?= aman($label) ?>
                            <?php if ($key === 'agama'): ?>
                                <select name="agama" required>
                                    <option value="">-- Pilih Agama --</option>
                                    <?php foreach (Mahasiswa::AGAMA as $agama): ?>
                                        <option value="<?= aman($agama) ?>" <?= $value === $agama ? 'selected' : '' ?>><?= aman($agama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input name="<?= aman($key) ?>" value="<?= aman($value) ?>"
                                    <?= $key === 'semester' ? 'type="number" min="1" max="14"' : 'type="text"' ?>
                                    <?= $key === 'nim' && $edit ? 'readonly' : '' ?>
                                    <?= $key !== 'hobi' ? 'required' : '' ?>>
                            <?php endif; ?></label>
                    <?php endforeach; ?>

                    <label>Jenis Mahasiswa
                        <select name="jenis" id="jenis" required onchange="cekPotongan()">
                            <option value="reguler" <?= $jenisEdit === 'reguler' || $jenisEdit === '' ? 'selected' : '' ?>>Reguler</option>
                            <option value="beasiswa" <?= $jenisEdit === 'beasiswa' ? 'selected' : '' ?>>Beasiswa</option>
                        </select>
                    </label>
                    <label>UKT (Rp)
                        <input name="ukt" type="number" min="0" step="1" value="<?= aman($uktEdit) ?>" required>
                    </label>
                    <label id="labelPotongan">Potongan (%)
                        <input name="potongan" id="potongan" type="number" min="0" max="100" step="1" value="<?= aman($potonganEdit) ?>">
                    </label>
                </div>
                <p><button><?= $edit ? 'Simpan Perubahan' : 'Tambah Mahasiswa' ?></button>
                    <?php if ($edit): ?><a class="btn" href="index.php">Batal</a><?php endif; ?></p>
            </form>
            <?php if ($edit): ?><p><small>NIM <strong><?= aman($edit->getNim()) ?></strong> bersifat tetap (read-only) dan tidak disediakan setter-nya. Jenis mahasiswa boleh diganti; mengganti jenis membuat object child baru pada NIM yang sama.</small></p><?php endif; ?>
            <script>
                function cekPotongan() {
                    var beasiswa = document.getElementById('jenis').value === 'beasiswa';
                    document.getElementById('potongan').required = beasiswa;
                }
                cekPotongan();
            </script>
        </section>
        <section class="panel">
            <h2>Daftar Mahasiswa (<?= count($daftar) ?>)</h2>
            <form method="get" class="cari">
                <input name="q" value="<?= aman($q) ?>" placeholder="Cari NIM atau nama...">
                <button>Cari</button>
                <a class="btn" href="index.php">Reset</a>
            </form>
            <?php if ($q !== ''): ?><p><small>Menampilkan hasil pencarian untuk "<strong><?= aman($q) ?></strong>". Data di session tidak berubah.</small></p><?php endif; ?>
            <div class="scroll">
                <table>
                    <thead>
                        <tr><?php foreach ($keys as $label): ?>
                                <th><?= aman($label) ?>
                                </th><?php endforeach; ?>
                            <th>Jenis</th>
                            <th>Biaya Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $m): ?>
                            <tr>
                                <td><?= aman($m->getNim()) ?></td>
                                <td><?= aman($m->getNama()) ?></td>
                                <td><?= aman($m->getFakultas()) ?></td>
                                <td><?= aman($m->getProdi()) ?></td>
                                <td><?= aman($m->getAsalSekolah()) ?></td>
                                <td><?= aman($m->getSemester()) ?></td>
                                <td><?= aman($m->getHobi()) ?></td>
                                <td><?= aman($m->getAgama()) ?></td>
                                <td><?= aman($m->getJenis()) ?></td>
                                <td><?= aman(number_format($m->hitungBiaya(), 0, ',', '.')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn" href="?edit=<?= urlencode($m->getNim()) ?>">Edit</a>
                                        <form method="post" onsubmit="return confirm('Hapus data ini?')">
                                            <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
                                            <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="nim" value="<?= aman($m->getNim()) ?>">
                                            <button class="danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                        <?php if (!$daftar): ?>
                            <tr>
                                <td colspan="11">Belum ada data.</td>
                            </tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p><small>Data disimpan pada PHP Session (sementara), bukan MySQL. Semua akses property lewat getter; biaya akhir dihitung oleh hitungBiaya() sesuai jenis mahasiswa.</small></p>
        </section>
    </main>
</body>

</html>
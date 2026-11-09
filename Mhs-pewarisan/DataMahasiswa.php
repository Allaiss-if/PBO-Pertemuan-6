<?php

declare(strict_types=1);
require_once __DIR__ . '/Mahasiswa.php';
require_once __DIR__ . '/MahasiswaReguler.php';
require_once __DIR__ . '/MahasiswaBeasiswa.php';

class DataMahasiswa
{
    /** @var array<string, Mahasiswa> */
    private array $data = [];

    /** @param array<string, Mahasiswa> $dataAwal */
    public function __construct(array $dataAwal = [])
    {
        foreach ($dataAwal as $item) {
            if ($item instanceof Mahasiswa) {
                $this->data[$item->getNim()] = $item;
            }
        }
    }

    public function tambah(Mahasiswa $m): bool
    {
        $nim = $m->getNim(); // Bukan $m->nim (private).
        if (isset($this->data[$nim])) return false;
        $this->data[$nim] = $m;
        return true;
    }

    /** @return array<string, Mahasiswa> */
    public function semua(): array
    {
        return $this->data;
    }

    public function cari(string $nim): ?Mahasiswa
    {
        return $this->data[$nim] ?? null;
    }

    public function ubah(string $nimLama, Mahasiswa $m): bool
    {
        if (!isset($this->data[$nimLama])) return false;
        if ($m->getNim() !== $nimLama) return false; // NIM tetap: tidak boleh berganti.
        $this->data[$nimLama] = $m; // Mengganti data di posisi yang sama (tidak dipindah ke akhir).
        return true;
    }

    public function hapus(string $nim): bool
    {
        if (!isset($this->data[$nim])) return false;
        unset($this->data[$nim]);
        return true;
    }
}

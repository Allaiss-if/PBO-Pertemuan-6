<?php

declare(strict_types=1);
require_once __DIR__ . '/Pegawai.php';

final class PegawaiKontrak extends Pegawai
{
    private int $upahPerJam;
    private int $jamKerja;

    public function __construct(string $id, string $nama, int $upahPerJam, int $jamKerja)
    {
        parent::__construct($id, $nama);
        if ($upahPerJam < 0) {
            throw new InvalidArgumentException('Upah per jam tidak boleh negatif.');
        }
        if ($jamKerja < 0) {
            throw new InvalidArgumentException('Jam kerja tidak boleh negatif.');
        }
        $this->upahPerJam = $upahPerJam;
        $this->jamKerja = $jamKerja;
    }

    public function getUpahPerJam(): int
    {
        return $this->upahPerJam;
    }

    public function getJamKerja(): int
    {
        return $this->jamKerja;
    }

    public function hitungGaji(): int
    {
        return $this->upahPerJam * $this->jamKerja;
    }
}

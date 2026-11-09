<?php
// SESUDAH (benar): Mahasiswa "memiliki" Alamat
class Alamat {
    public function __construct(private string $jalan, private string $kota) {}
    public function getJalan(): string { return $this->jalan; }
}

class Mahasiswa {
    // 8 property private tetap di sini...
    private Alamat $alamat;

    public function setAlamat(Alamat $alamat): void {
        $this->alamat = $alamat;
    }

    public function getAlamat(): Alamat {
        return $this->alamat;
    }
}

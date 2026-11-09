<?php
// SEBELUM (keliru): Alamat "adalah" Mahasiswa
class Alamat extends Mahasiswa {
    public function __construct(string $jalan, string $kota) {
        // Alamat dipaksa punya NIM, semester, dll. yang tidak relevan
    }
}

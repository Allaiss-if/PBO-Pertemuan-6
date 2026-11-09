<?php

declare(strict_types=1);
require_once __DIR__ . '/PegawaiTetap.php';
require_once __DIR__ . '/PegawaiKontrak.php';

$tetap = new PegawaiTetap('P001', 'Budi Santoso', 4500000);
$kontrak = new PegawaiKontrak('P002', 'Siti Aminah', 50000, 72);

echo 'Pegawai Tetap : ' . $tetap->getId() . ' - ' . $tetap->getNama()
    . ' | Gaji = Rp' . number_format($tetap->hitungGaji(), 0, ',', '.') . '<br>';
echo 'Pegawai Kontrak: ' . $kontrak->getId() . ' - ' . $kontrak->getNama()
    . ' | Gaji = Rp' . number_format($kontrak->hitungGaji(), 0, ',', '.')
    . ' (' . $kontrak->getJamKerja() . ' jam x Rp' . number_format($kontrak->getUpahPerJam(), 0, ',', '.') . ')';

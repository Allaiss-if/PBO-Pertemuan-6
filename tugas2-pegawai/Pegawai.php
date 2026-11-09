<?php

declare(strict_types=1);

abstract class Pegawai
{
    private string $id;
    private string $nama;

    public function __construct(string $id, string $nama)
    {
        $id = trim($id);
        $nama = trim($nama);
        if ($id === '') {
            throw new InvalidArgumentException('ID wajib diisi.');
        }
        if ($nama === '') {
            throw new InvalidArgumentException('Nama wajib diisi.');
        }
        $this->id = $id;
        $this->nama = $nama;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    abstract public function hitungGaji(): int;
}

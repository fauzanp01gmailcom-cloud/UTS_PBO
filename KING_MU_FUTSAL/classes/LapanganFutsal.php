<?php
/**
 * KELAS TURUNAN: inheritance dari kelas Lapangan.
 */
class LapanganFutsal extends Lapangan {
    private string $ukuran;
    private string $jenisRumput;
    private bool $tersediaParkir;
    private int $kapasitasPemain;
    private string $fasilitas;

    public function __construct(
        int $id, string $nama, string $jenis, int $hargaPerJam, string $status,
        string $ukuran = 'Standar', string $jenisRumput = 'Sintetis',
        bool $tersediaParkir = true, int $kapasitasPemain = 10,
        string $fasilitas = 'Toilet, mushola, ruang tunggu'
    ) {
        parent::__construct($id, $nama, $jenis, $hargaPerJam, $status);
        $this->ukuran = $ukuran;
        $this->jenisRumput = $jenisRumput;
        $this->tersediaParkir = $tersediaParkir;
        $this->kapasitasPemain = $kapasitasPemain;
        $this->fasilitas = $fasilitas;
    }

    public function getUkuran(): string { return $this->ukuran; }
    public function getJenisRumput(): string { return $this->jenisRumput; }
    public function getTersediaParkir(): bool { return $this->tersediaParkir; }
    public function getKapasitasPemain(): int { return $this->kapasitasPemain; }
    public function getFasilitas(): string { return $this->fasilitas; }

    public function ringkasan(): string {
        return $this->getNama() . ' • ' . $this->ukuran . ' • ' . $this->jenisRumput;
    }

    public function daftarFasilitas(): array {
        return array_map('trim', explode(',', $this->fasilitas));
    }

    public function detail(): array {
        return array_merge($this->informasi(), [
            'ukuran' => $this->ukuran,
            'rumput' => $this->jenisRumput,
            'parkir' => $this->tersediaParkir,
            'kapasitas' => $this->kapasitasPemain,
            'fasilitas' => $this->daftarFasilitas()
        ]);
    }
}

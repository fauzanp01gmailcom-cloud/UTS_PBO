<?php
/**
 * KELAS INDUK: contoh penerapan enkapsulasi.
 */
class Lapangan {
    private int $id;
    private string $nama;
    private string $jenis;
    private int $hargaPerJam;
    private string $status;

    public function __construct(int $id, string $nama, string $jenis, int $hargaPerJam, string $status = 'tersedia') {
        $this->id = $id;
        $this->nama = $nama;
        $this->jenis = $jenis;
        $this->hargaPerJam = $hargaPerJam;
        $this->status = $status;
    }

    public function getId(): int { return $this->id; }
    public function getNama(): string { return $this->nama; }
    public function getJenis(): string { return $this->jenis; }
    public function getHargaPerJam(): int { return $this->hargaPerJam; }
    public function getStatus(): string { return $this->status; }

    public function setStatus(string $status): void {
        if (in_array($status, ['tersedia', 'maintenance'], true)) {
            $this->status = $status;
        }
    }

    public function formatHarga(): string {
        return 'Rp ' . number_format($this->hargaPerJam, 0, ',', '.');
    }

    public function informasi(): array {
        return [
            'id' => $this->id, 'nama' => $this->nama, 'jenis' => $this->jenis,
            'harga' => $this->hargaPerJam, 'status' => $this->status
        ];
    }
}

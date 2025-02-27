<?php

namespace App\Services;

use App\Models\MasterSatuan;
use App\Models\TokoSatuan;
use App\Models\KonversiSatuan;
use Illuminate\Support\Facades\DB;

class MasterSatuanService
{
    public function getSatuanForToko($tokoId)
    {
        return MasterSatuan::whereHas('tokoSatuan', function ($query) use ($tokoId) {
            $query->where('id_toko', $tokoId)
                  ->where('is_active', true);
        })->with(['konversiAsal', 'konversiTujuan'])->get();
    }

    public function activateSatuanForToko($tokoId, array $satuanIds)
    {
        DB::transaction(function () use ($tokoId, $satuanIds) {
            // Nonaktifkan semua satuan toko terlebih dahulu
            TokoSatuan::where('id_toko', $tokoId)
                      ->update(['is_active' => false]);

            // Aktifkan satuan yang dipilih
            foreach ($satuanIds as $satuanId) {
                TokoSatuan::updateOrCreate(
                    [
                        'id_toko' => $tokoId,
                        'id_master_satuan' => $satuanId
                    ],
                    ['is_active' => true]
                );
            }
        });
    }

    public function getKonversiNilai($satuanAsalId, $satuanTujuanId)
    {
        // Cek konversi langsung
        $konversiLangsung = KonversiSatuan::where('satuan_asal_id', $satuanAsalId)
            ->where('satuan_tujuan_id', $satuanTujuanId)
            ->first();

        if ($konversiLangsung) {
            return $konversiLangsung->nilai_konversi;
        }

        // Cek konversi melalui satuan dasar
        $satuanAsal = MasterSatuan::find($satuanAsalId);
        $satuanTujuan = MasterSatuan::find($satuanTujuanId);

        if ($satuanAsal->satuan_dasar === $satuanTujuan->satuan_dasar) {
            $konversiKeSatuanDasar = $this->getKonversiKeSatuanDasar($satuanAsalId);
            $konversiDariSatuanDasar = $this->getKonversiDariSatuanDasar($satuanTujuanId);

            if ($konversiKeSatuanDasar && $konversiDariSatuanDasar) {
                return $konversiKeSatuanDasar * (1 / $konversiDariSatuanDasar);
            }
        }

        throw new \Exception('Konversi satuan tidak ditemukan');
    }

    private function getKonversiKeSatuanDasar($satuanId)
    {
        $satuan = MasterSatuan::find($satuanId);
        $satuanDasar = MasterSatuan::where('tipe_satuan', $satuan->tipe_satuan)
            ->where('kode_satuan', $satuan->satuan_dasar)
            ->first();

        if (!$satuanDasar) {
            return null;
        }

        $konversi = KonversiSatuan::where('satuan_asal_id', $satuanId)
            ->where('satuan_tujuan_id', $satuanDasar->id)
            ->first();

        return $konversi ? $konversi->nilai_konversi : null;
    }

    private function getKonversiDariSatuanDasar($satuanId)
    {
        $satuan = MasterSatuan::find($satuanId);
        $satuanDasar = MasterSatuan::where('tipe_satuan', $satuan->tipe_satuan)
            ->where('kode_satuan', $satuan->satuan_dasar)
            ->first();

        if (!$satuanDasar) {
            return null;
        }

        $konversi = KonversiSatuan::where('satuan_asal_id', $satuanDasar->id)
            ->where('satuan_tujuan_id', $satuanId)
            ->first();

        return $konversi ? $konversi->nilai_konversi : null;
    }
}
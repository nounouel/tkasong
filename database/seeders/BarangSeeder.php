<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;

class BarangSeeder extends Seeder
{
    public function run()
    {
        // Data dari file excel "data toko.xlsx"
        $data = [
            ['kategori' => 'MINYAK', 'nama_barang' => 'Fortune', 'satuan' => '1lt'],
            ['kategori' => 'MINYAK', 'nama_barang' => 'Bimoli', 'satuan' => '1lt'],
            ['kategori' => 'MINYAK', 'nama_barang' => 'Sania', 'satuan' => '1lt'],
            ['kategori' => 'MINYAK', 'nama_barang' => 'Fitri', 'satuan' => '1lt'],
            ['kategori' => 'MINYAK', 'nama_barang' => 'Tawon', 'satuan' => '1lt'],
            ['kategori' => 'MINYAK', 'nama_barang' => 'Tawon', 'satuan' => '500ml'],
            ['kategori' => 'KECAP', 'nama_barang' => 'Bango', 'satuan' => '200ml'],
            ['kategori' => 'KECAP', 'nama_barang' => 'Bango', 'satuan' => '600ml'],
            ['kategori' => 'KECAP', 'nama_barang' => 'PSP', 'satuan' => '520 ml'],
            ['kategori' => 'MIE KUNING', 'nama_barang' => 'PANDA', 'satuan' => '18pcs'],
            ['kategori' => 'MIE KUNING', 'nama_barang' => 'PANDA', 'satuan' => '24pcs'],
            ['kategori' => 'MIE KUNING', 'nama_barang' => 'BOLA MAS', 'satuan' => '12pcs'],
            ['kategori' => 'MIE KUNING', 'nama_barang' => '3 Ayam', 'satuan' => '200gr'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Goreng', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Kaldu Ayam', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Ayam Bawang', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Ayam Geprek', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Rendang', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Soto', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Goreng Aceh', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Soto Koya', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Mie Goreng Jumbo', 'satuan' => '24 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Rendang Jumbo', 'satuan' => '24 bks'],
            ['kategori' => 'MIE INDOFOOD', 'nama_barang' => 'Ayam Panggang Jumbo', 'satuan' => '24 bks'],
            ['kategori' => 'MIE SUPERMI', 'nama_barang' => 'Goreng', 'satuan' => '40 bks'],
            ['kategori' => 'MIE SEDAAP', 'nama_barang' => 'Goreng', 'satuan' => '40 bks'],
            ['kategori' => 'MIE SEDAAP', 'nama_barang' => 'Soto', 'satuan' => '40 bks'],
            ['kategori' => 'MIE SEDAAP', 'nama_barang' => 'Ayam Pop', 'satuan' => '40 bks'],
            ['kategori' => 'MIE SARIMI', 'nama_barang' => 'Goreng Ayam', 'satuan' => '40bks'],
            ['kategori' => 'MIE SARIMI', 'nama_barang' => 'Kaldu Ayam', 'satuan' => '40 bks'],
            ['kategori' => 'MIE SARIMI', 'nama_barang' => 'Soto', 'satuan' => '40 bks'],
            ['kategori' => 'MIE INTERMI', 'nama_barang' => 'Kaldu Ayam', 'satuan' => '40 bks'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Botol Abc', 'satuan' => '135ml'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Botol Abc', 'satuan' => '275ml'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Botol Abc', 'satuan' => '335ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Botol Abc', 'satuan' => '335ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Botol Abc', 'satuan' => '275ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Botol Abc', 'satuan' => '135ml'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Indofood', 'satuan' => '135ml'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Indofood', 'satuan' => '275ml'],
            ['kategori' => 'SAUS SAMBAL', 'nama_barang' => 'Indofood', 'satuan' => '335ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Indofood', 'satuan' => '135ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Indofood', 'satuan' => '275ml'],
            ['kategori' => 'SAUS TOMAT', 'nama_barang' => 'Indofood', 'satuan' => '335ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Dairy Charm', 'satuan' => '370ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Tiga Sapi', 'satuan' => '490ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Enak', 'satuan' => '370ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Enak Coklat', 'satuan' => '370ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Dairy Crown', 'satuan' => '1kg'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Frisian Flag', 'satuan' => '370ml'],
            ['kategori' => 'SUSU KALENG', 'nama_barang' => 'Frisian Flag', 'satuan' => '370ml'], // duplikat? biarkan saja
            ['kategori' => 'SIRUP', 'nama_barang' => 'Marjan Hijau', 'satuan' => '460ml'],
            ['kategori' => 'SIRUP', 'nama_barang' => 'Marjan Merah', 'satuan' => '460ml'],
            ['kategori' => 'SIRUP', 'nama_barang' => 'Abc Leci', 'satuan' => '460ml'],
            ['kategori' => 'SIRUP', 'nama_barang' => 'Abc Coco pandan', 'satuan' => '460ml'],
            ['kategori' => 'SIRUP', 'nama_barang' => 'Abc Jeruk', 'satuan' => '460ml'],
            ['kategori' => 'IKAN KALENG', 'nama_barang' => 'Sarden Botan', 'satuan' => '155gr'],
            ['kategori' => 'IKAN KALENG', 'nama_barang' => 'Sarden Botan', 'satuan' => '425gr'],
            ['kategori' => 'IKAN KALENG', 'nama_barang' => 'Kings Fisher', 'satuan' => '155gr'],
            ['kategori' => 'IKAN KALENG', 'nama_barang' => 'Kings Fisher', 'satuan' => '425gr'],
            ['kategori' => 'KECAP ASIN', 'nama_barang' => 'Kambing Kembar', 'satuan' => '320ml'],
            ['kategori' => 'KECAP ASIN', 'nama_barang' => 'Kambing Kembar', 'satuan' => '625ml'],
            ['kategori' => 'KECAP ASIN', 'nama_barang' => 'Gajah Borneo', 'satuan' => '300gr'],
            ['kategori' => 'KECAP ASIN', 'nama_barang' => 'Gajah Borneo', 'satuan' => '600ml'],
            ['kategori' => 'SAOS', 'nama_barang' => 'Saori', 'satuan' => '270 ml'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Ayam', 'satuan' => '100gr'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Sapi', 'satuan' => '100gr'],
            ['kategori' => 'ROYCO', 'nama_barang' => 'Sapi', 'satuan' => '8gr'],
            ['kategori' => 'ROYCO', 'nama_barang' => 'Ayam', 'satuan' => '8gr'],
            ['kategori' => 'ROYCO', 'nama_barang' => 'Ayam', 'satuan' => '100 gr'],
            ['kategori' => 'ROYCO', 'nama_barang' => 'Sapi', 'satuan' => '100gr'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Ayam', 'satuan' => '8,5 gr'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Sapi', 'satuan' => '8,5 gr'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Ayam', 'satuan' => '250 gr'],
            ['kategori' => 'MASAKO', 'nama_barang' => 'Sapi', 'satuan' => '250gr'],
            ['kategori' => 'SAMBAL', 'nama_barang' => 'Kaisar', 'satuan' => '340ml'],
            ['kategori' => 'SAMBAL', 'nama_barang' => '88', 'satuan' => '350gr'],
            ['kategori' => 'GARAM', 'nama_barang' => 'Refina', 'satuan' => '500gr'],
            ['kategori' => 'MICIN', 'nama_barang' => 'Ajinomoto', 'satuan' => '16 gr'],
            ['kategori' => 'MICIN', 'nama_barang' => 'Ajinomoto', 'satuan' => '50gr'],
            ['kategori' => 'MICIN', 'nama_barang' => 'Ajinomoto', 'satuan' => '90gr'],
            ['kategori' => 'MICIN', 'nama_barang' => 'Ajinomoto', 'satuan' => '100gr'],
            ['kategori' => 'MICIN', 'nama_barang' => 'Ajinomoto', 'satuan' => '120gr'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Sajiku', 'satuan' => '220gr'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Sajiku', 'satuan' => '500 gr'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Segitiga Biru', 'satuan' => '1kg'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Segitiga Biru', 'satuan' => '5kg'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Beras Rose Brand', 'satuan' => '500gr'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Ketan Rose Brand', 'satuan' => '500gr'],
            ['kategori' => 'TEPUNG', 'nama_barang' => 'Lencana Merah', 'satuan' => '25kg'],
            ['kategori' => 'SABUN CUCI', 'nama_barang' => 'Sunlight', 'satuan' => '420ml'],
            ['kategori' => 'SABUN CUCI', 'nama_barang' => 'Sunlight', 'satuan' => '210ml'],
            ['kategori' => 'SABUN CUCI', 'nama_barang' => 'Mama Lemon', 'satuan' => '420ml'],
            ['kategori' => 'MENTEGA', 'nama_barang' => 'Blueband', 'satuan' => '200gr'],
            ['kategori' => 'MENTEGA', 'nama_barang' => 'Forvita', 'satuan' => '200gr'],
            ['kategori' => 'MENTEGA', 'nama_barang' => 'Palmia', 'satuan' => '200gr'],
            ['kategori' => 'MENTEGA', 'nama_barang' => 'Filma', 'satuan' => '200gr'],
            ['kategori' => 'MENTEGA', 'nama_barang' => 'Amanda', 'satuan' => '200gr'],
            ['kategori' => 'DETERGENT', 'nama_barang' => 'Rinso', 'satuan' => '195gr'],
            ['kategori' => 'DETERGENT', 'nama_barang' => 'Daia', 'satuan' => '290gr'],
            ['kategori' => 'DETERGENT', 'nama_barang' => 'Boom', 'satuan' => '280gr'],
            ['kategori' => 'PEWANGI', 'nama_barang' => 'Molto', 'satuan' => '18ml'],
            ['kategori' => 'PEWANGI', 'nama_barang' => 'Rinso', 'satuan' => '40ml'],
            ['kategori' => 'PEWANGI', 'nama_barang' => 'Downy', 'satuan' => '20ml'],
            ['kategori' => 'PEWANGI', 'nama_barang' => 'So Klin', 'satuan' => '60ml'],
            ['kategori' => 'PEWANGI', 'nama_barang' => 'Royale', 'satuan' => '13ml'],
            ['kategori' => 'KERUPUK', 'nama_barang' => 'Emping Melinjo', 'satuan' => '1kg'],
            ['kategori' => 'TEH', 'nama_barang' => 'Bendera', 'satuan' => '50gr'],
            ['kategori' => 'TEH', 'nama_barang' => 'Prendjak', 'satuan' => '25pcs'],
            ['kategori' => 'TEH', 'nama_barang' => 'Poci', 'satuan' => '25pcs'],
            ['kategori' => 'TEH', 'nama_barang' => 'Sari Wangi', 'satuan' => '25pcs'],
            ['kategori' => 'TEH', 'nama_barang' => 'Tong Tji', 'satuan' => '25pcs'],
            ['kategori' => 'TEH', 'nama_barang' => 'Sosro Celup', 'satuan' => '25pcs'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Pandan Wangi', 'satuan' => '5kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Putri Koki', 'satuan' => '5kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Putri Koki', 'satuan' => '10kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Putri Koki', 'satuan' => '25kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Lai Garden', 'satuan' => '10kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Lai Garden', 'satuan' => '20kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Madu Tupai', 'satuan' => '10kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Madu Tupai', 'satuan' => '12kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Beras Hoki', 'satuan' => '20kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Kampung', 'satuan' => '10kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Mercy', 'satuan' => '5kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Mercy', 'satuan' => '40kg'],
            ['kategori' => 'BERAS', 'nama_barang' => 'Royal Regal', 'satuan' => '20 kg'],
        ];

        foreach ($data as $item) {
            $stokMinimum = $this->calculateStokMinimum($item['kategori'], $item['nama_barang'], $item['satuan']);
            Barang::create([
                'kategori'       => $item['kategori'],
                'nama_barang'    => $item['nama_barang'],
                'satuan'         => $item['satuan'],// default, bisa diisi manual nanti
                'stok_minimum'   => $stokMinimum,
            ]);
        }
    }

    /**
     * Menghitung stok minimum (safety stock) secara dinamis
     * agar data lebih bervariasi dan menyerupai data riil di toko.
     */
    private function calculateStokMinimum(string $kategori, string $namaBarang, string $satuan): int
    {
        $kategori = strtoupper($kategori);
        $satuanClean = strtolower(str_replace(' ', '', $satuan));
        $baseStokMinimum = 10; // default base safety stock

        // 1. Barang-barang berat / kemasan besar (stok minimum lebih kecil karena perputaran lambat)
        if (str_contains($satuanClean, '25kg') || str_contains($satuanClean, '40kg') || str_contains($satuanClean, '20kg') || (str_contains($satuanClean, '5kg') && $kategori === 'TEPUNG')) {
            $baseStokMinimum = 3;
        }
        // 2. Beras
        elseif ($kategori === 'BERAS') {
            if (str_contains($satuanClean, '25kg') || str_contains($satuanClean, '40kg') || str_contains($satuanClean, '20kg')) {
                $baseStokMinimum = 5;
            } else {
                $baseStokMinimum = 10; // untuk kemasan 5kg / 10kg / 12kg
            }
        }
        // 3. Sembako utama / Fast Moving
        elseif ($kategori === 'MINYAK') {
            $baseStokMinimum = (str_contains($satuanClean, '1lt') || str_contains($satuanClean, '1kg')) ? 18 : 10;
        }
        elseif ($kategori === 'MIE INDOFOOD' || $kategori === 'MIE SEDAAP') {
            $baseStokMinimum = 30; // Mi instan terpopuler, perputaran sangat cepat
        }
        elseif ($kategori === 'MIE SUPERMI' || $kategori === 'MIE SARIMI' || $kategori === 'MIE INTERMI') {
            $baseStokMinimum = 15; // Secondary brand mi instan
        }
        elseif ($kategori === 'MIE KUNING') {
            $baseStokMinimum = 12;
        }
        // 4. Susu Kaleng & Mentega
        elseif ($kategori === 'SUSU KALENG') {
            $baseStokMinimum = str_contains($satuanClean, '1kg') ? 8 : 22;
        }
        elseif ($kategori === 'MENTEGA') {
            $baseStokMinimum = 15;
        }
        // 5. Bumbu dapur eceran / Sachets (murah, cepat habis, stok minimum harus tinggi)
        elseif (in_array($kategori, ['MASAKO', 'ROYCO', 'MICIN', 'PEWANGI'])) {
            if (str_contains($satuanClean, 'gr') || str_contains($satuanClean, 'ml')) {
                preg_match('/(\d+[\d,]*)/', $satuanClean, $matches);
                if (isset($matches[1])) {
                    $weight = (float) str_replace(',', '.', $matches[1]);
                    if ($weight <= 20) {
                        $baseStokMinimum = 40; // sachet kecil
                    } elseif ($weight <= 100) {
                        $baseStokMinimum = 25; // kemasan sedang
                    }
                }
            } else {
                $baseStokMinimum = 20;
            }
        }
        // 6. Garam, Teh, Sabun Cuci, Detergent
        elseif ($kategori === 'DETERGENT' || $kategori === 'SABUN CUCI') {
            $baseStokMinimum = 16;
        }
        elseif ($kategori === 'GARAM') {
            $baseStokMinimum = 12;
        }
        elseif ($kategori === 'TEH') {
            $baseStokMinimum = 20;
        }
        // 7. Saus, Kecap, Sirup, Ikan Kaleng
        elseif (in_array($kategori, ['SAUS SAMBAL', 'SAUS TOMAT', 'SAMBAL', 'KECAP', 'KECAP ASIN', 'SAOS'])) {
            $baseStokMinimum = 12;
        }
        elseif ($kategori === 'SIRUP') {
            $baseStokMinimum = 8;
        }
        elseif ($kategori === 'IKAN KALENG') {
            $baseStokMinimum = 12;
        }
        elseif ($kategori === 'KERUPUK') {
            $baseStokMinimum = 6;
        }

        // Tambahkan variasi natural berdasarkan panjang nama barang (deterministic offset: -2 s.d +2)
        $variation = (strlen($namaBarang) % 5) - 2;
        
        return (int) max(2, $baseStokMinimum + $variation);
    }
}
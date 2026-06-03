<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Barang;
use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use App\Models\PenjualanAgregat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuzzyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fuzzy_create_page_only_shows_barang_with_stock_less_than_or_equal_to_rop()
    {
        $user = User::factory()->create();

        // Barang 1: Stock is 20, ROP is 30 (stok <= ROP -> should be included)
        $barang1 = Barang::create([
            'nama_barang' => 'Barang ROP',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);
        TransaksiMasuk::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);
        TransaksiKeluar::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 80 // Stock = 20
        ]);
        PenjualanAgregat::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-02',
            'total_terjual' => 10,
            'reorder_point' => 30
        ]);
        // Barang 2: Stock is 98, ROP is 9 (stok > ROP -> should NOT be included)
        $barang2 = Barang::create([
            'nama_barang' => 'Barang Aman',
            'satuan' => 'pcs',
            'stok_minimum' => 5,
        ]);
        TransaksiMasuk::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);
        TransaksiKeluar::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 2 // Stock = 98
        ]);
        PenjualanAgregat::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-02',
            'total_terjual' => 2,
            'reorder_point' => 9
        ]);

        $response = $this->actingAs($user)->get(route('fuzzy.create'));

        $response->assertStatus(200);
        $barangInView = $response->viewData('barang');
        
        $this->assertTrue($barangInView->contains('id', $barang1->id), 'Barang ROP should be present in the dropdown options');
        $this->assertFalse($barangInView->contains('id', $barang2->id), 'Barang Aman should not be present in the dropdown options');
    }

    public function test_barang_detail_api_returns_correct_stock_and_average_sales()
    {
        $user = User::factory()->create();

        $barang = Barang::create([
            'nama_barang' => 'Test Barang',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);

        TransaksiMasuk::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);

        TransaksiKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 30
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02',
            'total_terjual' => 30,
            'reorder_point' => 70
        ]);

        $response = $this->actingAs($user)->get(route('fuzzy.barang-detail', $barang->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'stok_aktual' => 70,
            'rata_penjualan' => 30.0,
            'kategori' => '-',
            'satuan' => 'pcs'
        ]);
    }

    public function test_fuzzy_predict_api_returns_correct_response_structure()
    {
        $user = User::factory()->create();

        $barang = Barang::create([
            'nama_barang' => 'Barang Predict',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);

        TransaksiMasuk::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);

        TransaksiKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 20
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02',
            'total_terjual' => 20,
            'reorder_point' => 30
        ]);

        $response = $this->actingAs($user)->get(route('fuzzy.predict', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'permintaan',
            'stok',
            'hasil_fuzzy',
            'steps' => [
                'nama_barang',
                'training_data' => [
                    'penjualan',
                    'persediaan_akhir',
                    'persediaan_awal',
                    'pembelian'
                ],
                'min_max' => [
                    'pembelian',
                    'penjualan',
                    'persediaan_akhir',
                    'persediaan_awal'
                ],
                'permintaan',
                'stok',
                'fuzzifikasi',
                'rules',
                'defuzzifikasi',
                'nilai',
                'kategori'
            ]
        ]);
    }

    public function test_store_fuzzy_calculates_and_saves_recommendation_and_redirects_successfully()
    {
        $user = User::factory()->create();

        $barang = Barang::create([
            'nama_barang' => 'Barang Simpan',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);

        // Create transaction data to avoid division by zero or errors
        TransaksiMasuk::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);

        TransaksiKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 20
        ]);

        $postData = [
            'id_barang' => $barang->id,
            'stok_sekarang' => 20,
            'rata2_penjualan' => 15,
            'tanggal' => '2026-05-15',
        ];

        $response = $this->actingAs($user)->post(route('fuzzy.store'), $postData);

        $response->assertRedirect(route('fuzzy.index'));
        $response->assertSessionHas('success', 'Data fuzzy berhasil ditambahkan');

        // Check if Fuzzy model is created
        $this->assertDatabaseHas('fuzzies', [
            'id_barang' => $barang->id,
            'rata_rata_penjualan_perhari' => 15, // average sales
            'stok' => 20,       // stock
            'tanggal' => '2026-05-15',
        ]);
    }

    public function test_delete_fuzzy_rekomendasi_successfully()
    {
        $user = User::factory()->create();

        $barang = Barang::create([
            'nama_barang' => 'Barang Hapus',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);

        $fuzzy = \App\Models\Fuzzy::create([
            'id_barang' => $barang->id,
            'permintaan' => 5,
            'stok' => 10,
            'hasil_fuzzy' => 'Sedikit',
            'nilai_crisp' => 20,
            'tanggal' => '2026-05-15',
        ]);

        $response = $this->actingAs($user)->delete(route('fuzzy.destroy', $fuzzy->id));

        $response->assertRedirect(route('fuzzy.index'));
        $response->assertSessionHas('success', 'Data fuzzy berhasil dihapus');

        $this->assertDatabaseMissing('fuzzies', ['id' => $fuzzy->id]);
    }
}


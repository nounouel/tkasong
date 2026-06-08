<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Barang;
use App\Models\PenjualanAgregat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanAgregatTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_penjualan_agregat()
    {
        $response = $this->get(route('penjualan-agregat.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_penjualan_agregat()
    {
        $user = User::factory()->create();
        $barang = Barang::create([
            'nama_barang' => 'Minyak Goreng',
            'satuan' => 'liter',
            'stok_minimum' => 10,
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-26',
            'total_terjual' => 150,
            'reorder_point' => 20
        ]);

        $response = $this->actingAs($user)->get(route('penjualan-agregat.index'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.penjualan_agregat.index');
        $response->assertSee('Minyak Goreng');
        $response->assertSee('150');
    }

    public function test_penjualan_agregat_filtering()
    {
        $user = User::factory()->create();
        $barang1 = Barang::create([
            'nama_barang' => 'Beras Pandan',
            'satuan' => 'kg',
            'stok_minimum' => 50,
        ]);
        $barang2 = Barang::create([
            'nama_barang' => 'Gula Pasir',
            'satuan' => 'kg',
            'stok_minimum' => 30,
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-20',
            'total_terjual' => 200,
            'reorder_point' => 100
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-25',
            'total_terjual' => 100,
            'reorder_point' => 60
        ]);

        // Filter by search name
        $response = $this->actingAs($user)->get(route('penjualan-agregat.index', ['search' => 'Beras']));
        $response->assertSee('Beras Pandan');
        $response->assertDontSee('Gula Pasir');

        // Filter by date range
        $response = $this->actingAs($user)->get(route('penjualan-agregat.index', [
            'start_date' => '2026-05-22',
            'end_date' => '2026-05-26'
        ]));
        $response->assertSee('Gula Pasir');
        $response->assertDontSee('Beras Pandan');
    }

    public function test_auto_synchronization_when_transaksi_keluar_changes()
    {
        $barang = Barang::create([
            'nama_barang' => 'Kopi Bubuk',
            'satuan' => 'bungkus',
            'stok_minimum' => 15,
        ]);

        // 1. Create a TransaksiKeluar
        $transaksi = \App\Models\TransaksiKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'jumlah' => 5,
            'keterangan' => 'Penjualan Kopi'
        ]);

        // Assert that a PenjualanAgregat was automatically created with correct ROP: (5 * 2) + 15 = 25
        $this->assertDatabaseHas('penjualan_agregat', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'total_terjual' => 5,
            'reorder_point' => 25
        ]);

        // 2. Update the TransaksiKeluar jumlah
        $transaksi->update(['jumlah' => 10]);

        // Assert that PenjualanAgregat was updated with correct ROP: (10 * 2) + 15 = 35
        $this->assertDatabaseHas('penjualan_agregat', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'total_terjual' => 10,
            'reorder_point' => 35
        ]);

        // 3. Delete the TransaksiKeluar
        $transaksi->delete();

        // Assert that the PenjualanAgregat record was deleted since total_terjual becomes 0
        $this->assertDatabaseMissing('penjualan_agregat', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27'
        ]);
    }

    public function test_auto_synchronization_when_barang_stok_minimum_changes()
    {
        $barang = Barang::create([
            'nama_barang' => 'Teh Kotak',
            'satuan' => 'pcs',
            'stok_minimum' => 10,
        ]);

        // Create TransaksiKeluar to generate PenjualanAgregat
        \App\Models\TransaksiKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'jumlah' => 8,
            'keterangan' => 'Penjualan Teh'
        ]);

        // ROP should be: (8 * 2) + 10 = 26
        $this->assertDatabaseHas('penjualan_agregat', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'reorder_point' => 26
        ]);

        // Update stok_minimum to 20
        $barang->update(['stok_minimum' => 20]);

        // ROP should be updated to: (8 * 2) + 20 = 36
        $this->assertDatabaseHas('penjualan_agregat', [
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'reorder_point' => 36
        ]);
    }

    public function test_unauthenticated_user_cannot_access_daily_sales()
    {
        $response = $this->get(route('penjualan-agregat.daily-sales', ['id_barang' => 1]));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_get_daily_sales_json()
    {
        $user = User::factory()->create();
        $barang = Barang::create([
            'nama_barang' => 'Minyak Goreng',
            'satuan' => 'liter',
            'stok_minimum' => 10,
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-26',
            'total_terjual' => 150,
            'reorder_point' => 20
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-27',
            'total_terjual' => 200,
            'reorder_point' => 25
        ]);

        $response = $this->actingAs($user)->get(route('penjualan-agregat.daily-sales', ['id_barang' => $barang->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'barang' => ['id', 'nama_barang'],
            'sales' => [
                '*' => ['tanggal', 'total_terjual']
            ]
        ]);
        
        $response->assertJsonFragment(['tanggal' => '2026-05-27', 'total_terjual' => 200]);
        $response->assertJsonFragment(['tanggal' => '2026-05-26', 'total_terjual' => 150]);
    }
}

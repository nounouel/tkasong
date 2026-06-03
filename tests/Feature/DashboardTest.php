<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Barang;
use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard_with_correct_data()
    {
        $user = User::factory()->create();

        $barang1 = Barang::create([
            'nama_barang' => 'Beras Pandan wangi',
            'satuan' => 'kg',
            'stok_minimum' => 50,
        ]);

        $barang2 = Barang::create([
            'nama_barang' => 'Gula Semut',
            'satuan' => 'kg',
            'stok_minimum' => 10,
        ]);

        // Add incoming stock
        TransaksiMasuk::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 100
        ]);

        TransaksiMasuk::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-01',
            'jumlah' => 15
        ]);

        // Add outgoing stock
        TransaksiKeluar::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 60 // Final stock 40, which is < minimum (50)
        ]);

        TransaksiKeluar::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-02',
            'jumlah' => 2 // Final stock 13, which is >= minimum (10)
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.home');
        
        $barangBawahMinimum = $response->viewData('barangBawahMinimum');
        $this->assertTrue($barangBawahMinimum->contains('nama_barang', 'Beras Pandan wangi'));
        $this->assertFalse($barangBawahMinimum->contains('nama_barang', 'Gula Semut'));

        $response->assertSee('Total Stok Masuk');
        $response->assertSee('Total Stok Keluar');
    }
}

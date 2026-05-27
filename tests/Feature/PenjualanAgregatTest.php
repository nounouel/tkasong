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
            'reorder_point' => 20
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-05-26',
            'total_terjual' => 150
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
            'reorder_point' => 100
        ]);
        $barang2 = Barang::create([
            'nama_barang' => 'Gula Pasir',
            'satuan' => 'kg',
            'stok_minimum' => 30,
            'reorder_point' => 60
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang1->id,
            'tanggal' => '2026-05-20',
            'total_terjual' => 200
        ]);

        PenjualanAgregat::create([
            'id_barang' => $barang2->id,
            'tanggal' => '2026-05-25',
            'total_terjual' => 100
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
}

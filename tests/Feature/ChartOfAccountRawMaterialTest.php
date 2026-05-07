<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChartOfAccountRawMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Buat parent COA "Persediaan Bahan Baku"
        ChartOfAccount::create([
            'code' => '114',
            'account_name' => 'Persediaan Bahan Baku',
            'account_group_name' => 'Current Assets',
            'description' => 'Akun induk untuk persediaan bahan baku',
            'opening_balance' => 0,
            'normal_balance_position' => 'debit',
            'is_active' => true,
            'parent_code' => null,
        ]);
    }

    /** @test */
    public function it_creates_coa_for_first_raw_material()
    {
        // Test untuk raw material pertama
        $coa = ChartOfAccount::createRawMaterialAccount('Ayam Potong');

        $this->assertNotNull($coa);
        $this->assertEquals('1141', $coa->code);
        $this->assertEquals('Persediaan Bahan Baku Ayam Potong', $coa->account_name);
        $this->assertEquals('114', $coa->parent_code);
        $this->assertEquals('debit', $coa->normal_balance_position);
        $this->assertTrue($coa->is_active);
    }

    /** @test */
    public function it_creates_sequential_coa_codes()
    {
        // Buat beberapa raw material
        $coa1 = ChartOfAccount::createRawMaterialAccount('Ayam Potong');
        $coa2 = ChartOfAccount::createRawMaterialAccount('Tepung Terigu');
        $coa3 = ChartOfAccount::createRawMaterialAccount('Minyak Goreng');

        $this->assertEquals('1141', $coa1->code);
        $this->assertEquals('1142', $coa2->code);
        $this->assertEquals('1143', $coa3->code);
    }

    /** @test */
    public function it_handles_duplicate_codes()
    {
        // Buat COA manual dengan kode 1142
        ChartOfAccount::create([
            'code' => '1142',
            'account_name' => 'Manual Account',
            'account_group_name' => 'Current Assets',
            'normal_balance_position' => 'debit',
            'is_active' => true,
            'parent_code' => '114',
        ]);

        // Buat raw material, seharusnya melewati 1142
        $coa = ChartOfAccount::createRawMaterialAccount('Test Material');

        $this->assertEquals('1143', $coa->code);
    }

    /** @test */
    public function it_returns_null_if_parent_not_found()
    {
        // Hapus parent COA
        ChartOfAccount::where('code', '114')->delete();

        $coa = ChartOfAccount::createRawMaterialAccount('Test Material');

        $this->assertNull($coa);
    }

    /** @test */
    public function it_uses_database_transaction()
    {
        // Test untuk memastikan transaction berjalan
        // Ini akan gagal karena constraint violation
        $this->expectException(\Exception::class);

        // Coba buat COA dengan data yang invalid
        ChartOfAccount::createRawMaterialAccount(str_repeat('A', 300)); // nama terlalu panjang
    }
}

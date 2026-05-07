<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\BillOfMaterial;
use App\Http\Controllers\BillOfMaterialController;
use Illuminate\Http\Request;

class TestBOMCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test BOM creation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing BOM creation...');
        
        // Check if products exist
        $products = Product::count();
        $this->info("Products count: {$products}");
        
        if ($products === 0) {
            $this->error('No products found. Please create products first.');
            return 0;
        }
        
        // Get first product
        $product = Product::first();
        $this->info("Using product: {$product->name} (ID: {$product->id})");
        
        // Check raw materials
        $rawMaterials = RawMaterial::count();
        $this->info("Raw materials count: {$rawMaterials}");
        
        if ($rawMaterials === 0) {
            $this->error('No raw materials found. Please create raw materials first.');
            return 0;
        }
        
        // Get first raw material
        $rawMaterial = RawMaterial::first();
        $this->info("Using raw material: {$rawMaterial->name} (ID: {$rawMaterial->id})");
        
        // Test data
        $testData = [
            'product_id' => $product->id,
            'description' => 'Test BOM',
            'production_time_minutes' => 30,
            'items' => [
                [
                    'raw_material_id' => $rawMaterial->id,
                    'quantity' => 1,
                    'unit' => $rawMaterial->unit,
                    'unit_cost' => $rawMaterial->price_per_unit,
                ]
            ]
        ];
        
        $this->info('Test data prepared:');
        $this->info('Product ID: ' . $testData['product_id']);
        $this->info('Production Time: ' . $testData['production_time_minutes'] . ' minutes');
        $this->info('Items count: ' . count($testData['items']));
        
        // Test BOM creation
        try {
            $request = new Request();
            $request->merge($testData);
            
            $controller = new BillOfMaterialController();
            $response = $controller->store($request);
            
            $this->info('BOM created successfully!');
            
            // Check if BOM was created
            $bom = BillOfMaterial::latest()->first();
            if ($bom) {
                $this->info("BOM ID: {$bom->id}");
                $this->info("BOM Name: {$bom->name}");
                $this->info("BOM Code: {$bom->code}");
            }
            
        } catch (\Exception $e) {
            $this->error('Error creating BOM: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
        
        return 0;
    }
}

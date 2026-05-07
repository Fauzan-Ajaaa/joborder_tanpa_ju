<?php

use App\Models\Unit;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$models = [Unit::class, ChartOfAccount::class];

foreach ($models as $model) {
    echo "Processing $model...\n";
    // Force get everything without scope
    $all = $model::withoutGlobalScopes()->orderBy('id')->get();
    
    $seen = [];
    $delCount = 0;
    
    foreach ($all as $record) {
        $key = ($record->company_id ?: 'NULL') . '-' . $record->code;
        if (isset($seen[$key])) {
            // Force delete without scope
            $model::withoutGlobalScopes()->where('id', $record->id)->delete();
            $delCount++;
        } else {
            $seen[$key] = true;
        }
    }
    echo "Deleted $delCount duplicates for $model\n";
}

echo "Done.\n";

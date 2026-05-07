<?php

$createFile = 'resources/views/purchases/create.blade.php';
$editFile = 'resources/views/purchases/edit.blade.php';

$createContent = file_get_contents($createFile);
$createScriptMatch = [];
preg_match('/<script>.*<\/script>/us', $createContent, $createScriptMatch);
$createScript = $createScriptMatch[0];

$initLogic = <<<EOT
    const existingItems = @json(\$purchase->items);

    document.addEventListener('DOMContentLoaded', function() {
        filterMaterialsBySupplier();
        
        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => {
                addItem();
                const idx = itemIndex - 1;
                
                const aux = !!item.auxiliary_material_id;
                const matSel = aux 
                   ? document.querySelector(\`select[name="items[\${idx}][auxiliary_material_id]"]\`)
                   : document.querySelector(\`select[name="items[\${idx}][raw_material_id]"]\`);
                
                if (matSel) {
                    matSel.value = aux ? item.auxiliary_material_id : item.raw_material_id;
                    updateMaterialInfo(idx);
                }
                
                const unitSel = document.getElementById(\`unit-\${idx}\`);
                if (unitSel && item.unit) {
                    unitSel.value = item.unit;
                    onUnitChange(idx);
                }

                const taxSel = document.querySelector(\`select[name="items[\${idx}][tax_type]"]\`);
                if (taxSel && item.tax_type) {
                    taxSel.value = item.tax_type;
                }

                const qDisp = document.querySelector(\`input[name="items[\${idx}][quantity_display]"]\`);
                if (qDisp) {
                    qDisp.value = item.quantity;
                }

                const convInput = document.getElementById(\`conversion-factor-\${idx}\`);
                if (convInput) {
                    convInput.value = item.conversion_factor || 1;
                    onConversionFactorChange(idx);
                }
                
                // Set price last so it marks as manual if needed
                const pDisp = document.querySelector(\`input[name="items[\${idx}][unit_price_display]"]\`);
                if (pDisp) {
                    pDisp.value = item.unit_price;
                    onManualPriceInput(idx);
                }
                
                calculateItemSubtotal(idx);
            });
        } else {
            addItem();
        }

        toggleFobCost();
        toggleDueDate();
        toggleNewSupplierFields();
        calculateTotals();
    });
EOT;

$createScript = preg_replace('/document\.addEventListener\(\'DOMContentLoaded\', function\(\) \{.*?\}\);/us', $initLogic, $createScript);

$editContent = file_get_contents($editFile);
$editContentNew = preg_replace('/<script>.*<\/script>/us', $createScript, $editContent);
file_put_contents($editFile, $editContentNew);

echo 'Done';

-- Query untuk cek data purchase items untuk bahan baku ayam
SELECT 
    pi.id,
    pi.quantity,
    pi.unit_price,
    pi.tax_type,
    p.purchase_number,
    p.purchase_date,
    p.ppn_rate,
    p.discount_rate,
    p.status,
    -- Hitung DPP
    CASE 
        WHEN pi.tax_type = 'after_tax' AND p.ppn_rate > 0 
        THEN pi.unit_price / (1 + (p.ppn_rate / 100))
        ELSE pi.unit_price
    END as dpp_per_unit,
    -- Hitung harga efektif setelah diskon
    CASE 
        WHEN p.discount_rate > 0 
        THEN (CASE 
            WHEN pi.tax_type = 'after_tax' AND p.ppn_rate > 0 
            THEN pi.unit_price / (1 + (p.ppn_rate / 100))
            ELSE pi.unit_price
        END) * (1 - (p.discount_rate / 100))
        ELSE (CASE 
            WHEN pi.tax_type = 'after_tax' AND p.ppn_rate > 0 
            THEN pi.unit_price / (1 + (p.ppn_rate / 100))
            ELSE pi.unit_price
        END)
    END as effective_price
FROM purchase_items pi
JOIN purchases p ON pi.purchase_id = p.id
JOIN raw_materials rm ON pi.raw_material_id = rm.id
WHERE rm.code = 'BB-0000002'  -- Kode ayam
    AND p.status IN ('draft', 'approved', 'received')
ORDER BY p.created_at ASC;

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const formatRupiah = (number) => {
        const n = Number(number || 0);
        if (!n) {
            return '';
        }
        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
        }).format(Math.round(n));
    };

    const cleanNumber = (value) => {
        if (!value) return '';
        // buang karakter selain angka, koma, titik
        let cleaned = value.toString().replace(/[^0-9.,]/g, '');
        // hilangkan pemisah ribuan titik
        cleaned = cleaned.replace(/\./g, '');
        // ganti koma jadi titik untuk desimal
        cleaned = cleaned.replace(/,/g, '.');
        return cleaned;
    };

    const setupCurrencyInput = (input) => {
        if (!input) return;

        const applyFormat = () => {
            const raw = cleanNumber(input.value);
            if (!raw) {
                input.value = '';
                return;
            }
            const num = parseFloat(raw);
            input.value = formatRupiah(isNaN(num) ? 0 : num);
        };

        // format nilai awal jika sudah ada
        if (input.value) {
            applyFormat();
        }

        input.addEventListener('input', () => {
            const cursorEnd = input.selectionEnd;
            applyFormat();
            // posisi kursor mungkin bergeser, tapi untuk case sederhana ini cukup
            input.selectionEnd = input.value.length;
        });
    };

    document.querySelectorAll('input.currency-input').forEach(setupCurrencyInput);

    // sebelum submit form, bersihkan jadi angka polos
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('input.currency-input').forEach((input) => {
                const raw = cleanNumber(input.value);
                input.value = raw || '0';
            });
        });
    });
});

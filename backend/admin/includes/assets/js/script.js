// backend/admin/assets/js/scripts.js

document.addEventListener('DOMContentLoaded', function() {
    // --- 1. Fungsi Konfirmasi Penghapusan Data ---
    // Digunakan pada halaman manajemen data users (data_users.php)
    function confirmDelete(event) {
        // Mencegah aksi default (link) jika konfirmasi dibatalkan
        if (!confirm('Apakah Anda yakin ingin menghapus data ini secara permanen? Aksi ini tidak dapat dibatalkan.')) {
            event.preventDefault();
        }
    }

    // Pasang event listener ke semua tombol/link dengan class 'delete-btn'
    var deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', confirmDelete);
    });
    
    // --- 2. Implementasi Dynamic Search/Filter (Contoh) ---
    // Digunakan di halaman rekap_absensi.php
    var searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var filter = searchInput.value.toUpperCase();
            var table = document.getElementById('absensi-table');
            var tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) { // Mulai dari 1 untuk lewati header
                var td_name = tr[i].getElementsByTagName('td')[1]; // Asumsi Nama di kolom kedua (index 1)
                
                if (td_name) {
                    var txtValue = td_name.textContent || td_name.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        });
    }

    // --- 3. Animasi Sidebar Toggle (Contoh untuk Tampilan Mobile) ---
    var menuToggle = document.getElementById('menu-toggle');
    var sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});


// --- CATATAN PENTING ---
// Untuk fitur Export to Excel (.xlsx) yang canggih (client-side),
// Anda perlu mengintegrasikan library JS pihak ketiga seperti SheetJS (js-xlsx)
// atau menggunakan library PHP (seperti PhpSpreadsheet) di sisi server (lebih disarankan).

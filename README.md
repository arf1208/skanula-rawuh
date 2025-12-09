# 🏫 Sistem Absensi Real-Time RFID & WA Bot - SMK NU LAMONGAN

![Absensi RFID System Diagram]

Sistem ini dirancang untuk mencatat absensi Guru dan Siswa secara *real-time* menggunakan kartu RFID (MFRC522) yang terintegrasi dengan mikrokontroler **ESP32**, mengirim data ke *server* PHP/MySQL, dan memberikan notifikasi langsung ke WhatsApp orang tua/guru.

---

## 🎯 Fitur Utama

* **Absensi Cepat:** Pencatatan waktu Masuk dan Pulang otomatis berdasarkan tap kartu RFID.
* **Real-time API:** Komunikasi data antara ESP32 dan Server menggunakan HTTP GET.
* **Website Admin:** Dashboard untuk memantau data harian, filter, dan manajemen pengguna (CRUD).
* **WhatsApp Notification (Skema Integrasi):** Mengirim notifikasi otomatis setelah absensi dicatat ke nomor WA yang terdaftar (WA Ortu untuk siswa, WA Pribadi untuk guru).
* **Manajemen Otomatis:** Sistem mendeteksi Hari Libur (Weekend dan Tanggal Merah).

---

## 🛠️ Persyaratan Sistem

Pastikan Anda telah menginstal dan menyiapkan lingkungan berikut:

### I. 💻 Software Server (0 Biaya)

| Komponen | Versi / Tools | Keterangan |
| :--- | :--- | :--- |
| **Local Server** | Laragon (Apache & MySQL) | Digunakan untuk menjalankan PHP dan Database. |
| **Bahasa** | PHP 7.x atau lebih baru | Digunakan untuk *backend* website admin dan API. |
| **Database** | MySQL / MariaDB | Penyimpanan data absensi. |

### II. 🔌 Software Hardware (Arduino IDE)

| Komponen | Keterangan |
| :--- | :--- |
| **IDE** | Arduino IDE / VS Code dengan Ekstensi PlatformIO | Untuk meng-*upload* *firmware* ke ESP32. |
| **Library Wajib** | `WiFi`, `HTTPClient`, `SPI`, `MFRC522`, `LiquidCrystal_I2C` | Diperlukan untuk komunikasi dan sensor. |

---

## ⚙️ Struktur Proyek

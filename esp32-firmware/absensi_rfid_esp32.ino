#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <LiquidCrystal_I2C.h>

// --- KONFIGURASI PINS (Sesuai Base Plate 30 Pin) ---
#define SS_PIN 5      // RC522 SDA (SS) ke GPIO 5
#define RST_PIN 4     // RC522 RST ke GPIO 4
#define BUZZER_PIN 2  // Buzzer ke GPIO 2
// I2C: SDA=GPIO 21, SCL=GPIO 22 (Otomatis oleh Wire.begin(21, 22))

// --- KONFIGURASI JARINGAN & SERVER ---
const char* ssid = "WIFI_SEKOLAH_SMK_NU"; // << GANTI NAMA WIFI ANDA!
const char* password = "PASSWORD_WIFI";   // << GANTI PASSWORD WIFI ANDA!
// Ganti IP dengan IP Laragon Anda dan path ke api.php
// Pastikan Laragon Server sudah running dan IP berada di jaringan yang sama
const char* serverApiUrl = "http://192.168.x.x/absensi-rfid-smknu/api/api.php"; // << GANTI IP SERVER LARAGON ANDA!

// --- INSTANSIASI OBJEK ---
MFRC522 mfrc522(SS_PIN, RST_PIN);
// Coba alamat I2C 0x27 atau 0x3F. Jika tidak tampil, ganti 0x27
LiquidCrystal_I2C lcd(0x27, 16, 2); 

// --- DEKLARASI FUNGSI ---
void connectWiFi();
String readRfidUid();
void sendDataToServer(String uid);
void lcdFeedback(String line1, String line2, int delayMs);
void buzzerBeep(int count, int duration);

// ===============================================
// === SETUP ===
// ===============================================
void setup() {
    Serial.begin(115200);
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW); // Matikan buzzer

    // Inisialisasi LCD
    lcd.init();
    lcd.backlight();
    lcdFeedback("System Absensi", "SMK NU LAMONGAN", 2000);

    SPI.begin();
    mfrc522.PCD_Init();
    
    // Mulai koneksi WiFi
    connectWiFi();
    lcdFeedback("Siap Absen", "Tap Kartu RFID...", 1000);
}

// ===============================================
// === LOOP ===
// ===============================================
void loop() {
    if (WiFi.status() != WL_CONNECTED) {
        lcdFeedback("WiFi Terputus!", "Mencoba Reconnect", 500);
        connectWiFi();
        return;
    }

    String uid = readRfidUid();
    
    if (uid != "") {
        Serial.print("Kartu Terbaca: ");
        Serial.println(uid);
        lcdFeedback("Tap Terdeteksi", "Memproses Data...", 300);
        sendDataToServer(uid);
    }
    delay(100);
}

// ===============================================
// === IMPLEMENTASI FUNGSI HELPER ===
// ===============================================

/**
 * Fungsi untuk menghubungkan ESP32 ke Jaringan WiFi.
 */
void connectWiFi() {
    lcdFeedback("Connecting...", ssid, 0);
    WiFi.begin(ssid, password);
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 40) { // Coba 20 detik
        delay(500);
        Serial.print(".");
        attempts++;
    }
    if (WiFi.status() == WL_CONNECTED) {
        lcdFeedback("Connected!", WiFi.localIP().toString(), 2000);
        buzzerBeep(1, 150);
    } else {
        lcdFeedback("Koneksi Gagal!", "Mohon Cek WiFi", 5000);
        buzzerBeep(3, 300);
    }
}

/**
 * Fungsi untuk membaca UID RFID dari modul RC522.
 * @return String UID dalam format HEX (atau string kosong jika gagal).
 */
String readRfidUid() {
    if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
        return "";
    }
    String content = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
        // Format UID dengan 2 digit HEX
        content.concat(String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : ""));
        content.concat(String(mfrc522.uid.uidByte[i], HEX));
    }
    mfrc522.PICC_HaltA(); // Hentikan komunikasi PICC
    mfrc522.PCD_StopCrypto1(); // Hentikan enkripsi PICC
    content.toUpperCase();
    return content;
}

/**
 * Fungsi untuk mengirim data UID ke server PHP (Laragon) melalui HTTP GET.
 * @param uid UID RFID yang akan dikirim.
 */
void sendDataToServer(String uid) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        
        // Buat URL lengkap: http://192.168.x.x/.../api.php?rfid_uid=A1B2C3D4
        String serverPath = serverApiUrl + String("?rfid_uid=") + uid;

        http.begin(

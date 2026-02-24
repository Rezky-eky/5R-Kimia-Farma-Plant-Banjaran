# Instruksi Membersihkan Cache dan Memastikan Vite Berjalan

## 🔍 Masalah yang Ditemukan

1. **Konfigurasi Vite di app.blade.php salah** - Sudah diperbaiki
2. **Vite dev server mungkin tidak berjalan**
3. **Cache browser/Laravel mungkin perlu dibersihkan**

## ✅ Langkah-langkah Perbaikan

### 1. Perbaiki Konfigurasi Vite (SUDAH DIPERBAIKI)
File `resources/views/app.blade.php` sudah diperbaiki dari:
```php
@vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
```
Menjadi:
```php
@vite(['resources/js/app.js'])
```

### 2. Bersihkan Cache Laravel

Jalankan perintah berikut di terminal (dalam direktori project):

```bash
# Bersihkan semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Atau gunakan satu perintah untuk semua
php artisan optimize:clear
```

### 3. Hapus Build Assets Lama

```bash
# Hapus folder build di public
rm -rf public/build

# Atau di Windows PowerShell:
Remove-Item -Recurse -Force public\build
```

### 4. Install/Update Dependencies (Jika Perlu)

```bash
# Install npm dependencies
npm install

# Atau jika ada masalah, hapus node_modules dan install ulang
rm -rf node_modules package-lock.json
npm install
```

### 5. Jalankan Vite Dev Server

**PENTING:** Vite dev server HARUS berjalan saat development!

```bash
# Jalankan Vite dev server
npm run dev
```

Atau jika menggunakan Laravel Sail:
```bash
sail npm run dev
```

**Pastikan terminal ini tetap terbuka!** Vite dev server akan:
- Watch perubahan file
- Auto-reload browser
- Compile Vue/JS files secara real-time

### 6. Build untuk Production (Jika Deploy)

Jika Anda di production atau tidak ingin menjalankan dev server:

```bash
npm run build
```

Ini akan membuat build files di `public/build/`

### 7. Bersihkan Cache Browser

1. **Chrome/Edge:**
   - Tekan `Ctrl + Shift + Delete`
   - Pilih "Cached images and files"
   - Atau tekan `Ctrl + F5` untuk hard refresh

2. **Firefox:**
   - Tekan `Ctrl + Shift + Delete`
   - Pilih "Cache"
   - Atau tekan `Ctrl + F5`

3. **Atau gunakan Incognito/Private mode** untuk test

### 8. Verifikasi Route

Pastikan route `/` memanggil komponen yang benar:

File: `routes/web.php` (Line 12-19)
```php
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});
```

Ini akan memanggil: `resources/js/Pages/Welcome.vue` ✅

### 9. Cek File Welcome.vue

Pastikan file `resources/js/Pages/Welcome.vue` ada dan berisi perubahan Anda.

### 10. Cek Console Browser

Buka Developer Tools (F12) dan cek:
- **Console tab:** Apakah ada error?
- **Network tab:** Apakah file JS/Vue ter-load?
- **Sources tab:** Apakah file yang di-load adalah file terbaru?

## 🚀 Quick Start (Langkah Cepat)

Jalankan perintah ini secara berurutan:

```bash
# 1. Bersihkan cache Laravel
php artisan optimize:clear

# 2. Hapus build lama
rm -rf public/build

# 3. Install dependencies (jika belum)
npm install

# 4. Jalankan Vite dev server (TERPENTING!)
npm run dev
```

**Biarkan terminal `npm run dev` tetap berjalan**, lalu buka browser dan akses aplikasi Anda.

## ⚠️ Troubleshooting

### Masalah: "Vite client can't connect"
- Pastikan `npm run dev` sedang berjalan
- Cek port 5173 tidak digunakan aplikasi lain
- Cek firewall tidak memblokir port

### Masalah: "Module not found"
- Jalankan `npm install` lagi
- Hapus `node_modules` dan install ulang

### Masalah: Perubahan tidak muncul
- Hard refresh browser: `Ctrl + F5`
- Cek apakah Vite dev server masih berjalan
- Cek console browser untuk error

### Masalah: "Cannot GET /"
- Pastikan Laravel server berjalan: `php artisan serve`
- Atau jika pakai Sail: `sail up`

## 📝 Checklist

- [ ] Konfigurasi `app.blade.php` sudah benar
- [ ] Cache Laravel sudah dibersihkan
- [ ] Build assets lama sudah dihapus
- [ ] `npm install` sudah dijalankan
- [ ] `npm run dev` sedang berjalan
- [ ] Browser cache sudah dibersihkan
- [ ] File `Welcome.vue` sudah diubah
- [ ] Route `/` memanggil `Welcome` component

## 🎯 Verifikasi

Setelah semua langkah di atas:

1. Buka browser
2. Akses `http://localhost:8000` (atau URL aplikasi Anda)
3. Tekan `Ctrl + F5` untuk hard refresh
4. Perubahan seharusnya sudah muncul!

Jika masih belum muncul, cek console browser (F12) untuk melihat error yang mungkin terjadi.


# Dokumentasi Testing 5R

## Cakupan

Suite otomatis saat ini mencakup:

- PHPUnit feature dan unit test Laravel.
- Login berbasis NPP, logout, password confirmation, password update, dan registrasi yang dinonaktifkan.
- Otorisasi guest, user biasa, tim 5R, dan ketua 5R.
- Isolasi notifikasi: daftar, tandai semua dibaca, dan perlindungan terhadap notifikasi milik user lain.
- Profile update/delete.
- Filter dan pagination Go Offer.
- Smoke test browser untuk halaman login, redirect dashboard, dan route registrasi.
- Build frontend Vite/Vue.

## Menjalankan Test

Semua perintah backend dijalankan dari container agar memakai PHP dan MySQL yang sesuai:

```powershell
docker compose up -d
docker compose exec -T laravel.test php artisan migrate:fresh --database=mysql --force
docker compose exec -T laravel.test php artisan test
```

Menjalankan subset backend:

```powershell
docker compose exec -T laravel.test php artisan test tests/Feature/NotificationTest.php tests/Feature/AuthorizationTest.php
```

Menjalankan frontend dan E2E:

```powershell
npm run build
npm run test:e2e
npm run test:e2e:report
```

Playwright memakai `http://127.0.0.1:8000` dan akan menjalankan `php artisan serve` otomatis jika belum ada server yang aktif.

## Hasil Pengujian

Tanggal pengujian: 5 September 2026.

### Ringkasan Eksekusi

| No. | Pemeriksaan | Environment | Hasil | Status |
| --- | --- | --- | --- | --- |
| 1 | PHPUnit penuh | Docker PHP 8.4 + MySQL 8 | 27 lulus, 6 gagal, 33 test, 74 assertions | Sebagian lulus |
| 2 | Feature test fokus | Docker PHP 8.4 + MySQL 8 | 13 lulus, 44 assertions | Lulus |
| 3 | Playwright E2E smoke | Chromium | 3 lulus dari 3 test | Lulus |
| 4 | Vite production build | Node.js + Vite | Build berhasil | Lulus |
| 5 | PHPUnit langsung di host | PHP Windows | Tidak dapat berjalan karena driver `pdo_mysql` tidak tersedia | Tertahan environment |

### Detail PHPUnit

| No. | File/Suite | Test case | Verifikasi | Hasil | Keterangan |
| --- | --- | --- | --- | --- | --- |
| 1 | `tests/Unit/ExampleTest.php` | `that true is true` | Smoke test unit PHPUnit | Lulus |  |
| 2 | `tests/Feature/Auth/AuthenticationTest.php` | `login screen can be rendered` | Halaman login dapat dibuka | Lulus |  |
| 3 | `tests/Feature/Auth/AuthenticationTest.php` | `users can authenticate using the login screen` | Login menggunakan NPP dan password valid | Lulus |  |
| 4 | `tests/Feature/Auth/AuthenticationTest.php` | `users can not authenticate with invalid password` | Password salah ditolak | Lulus |  |
| 5 | `tests/Feature/Auth/AuthenticationTest.php` | `users can logout` | Session user dihapus saat logout | Lulus |  |
| 6 | `tests/Feature/Auth/EmailVerificationTest.php` | `email verification screen can be rendered` | Halaman verifikasi email | Gagal | Factory masih memanggil `unverified()`, sedangkan schema tidak lagi memiliki `email_verified_at` |
| 7 | `tests/Feature/Auth/EmailVerificationTest.php` | `email can be verified` | Verifikasi email dengan signed URL | Gagal | Factory `unverified()` tidak tersedia dan kontrak email sudah dihapus |
| 8 | `tests/Feature/Auth/EmailVerificationTest.php` | `email is not verified with invalid hash` | Hash verifikasi tidak valid ditolak | Gagal | Factory `unverified()` tidak tersedia dan kontrak email sudah dihapus |
| 9 | `tests/Feature/Auth/PasswordConfirmationTest.php` | `confirm password screen can be rendered` | Halaman konfirmasi password | Lulus |  |
| 10 | `tests/Feature/Auth/PasswordConfirmationTest.php` | `password can be confirmed` | Password valid dapat dikonfirmasi | Lulus |  |
| 11 | `tests/Feature/Auth/PasswordConfirmationTest.php` | `password is not confirmed with invalid password` | Password salah ditolak | Lulus |  |
| 12 | `tests/Feature/Auth/PasswordResetTest.php` | `reset password link screen can be rendered` | Halaman reset password | Lulus | Rendering saja berhasil |
| 13 | `tests/Feature/Auth/PasswordResetTest.php` | `reset password link can be requested` | Link reset password dikirim | Gagal | Proses masih mencari user berdasarkan kolom `email` yang sudah dihapus |
| 14 | `tests/Feature/Auth/PasswordResetTest.php` | `reset password screen can be rendered` | Halaman reset dengan token | Gagal | Token tidak pernah dibuat karena lookup email gagal |
| 15 | `tests/Feature/Auth/PasswordResetTest.php` | `password can be reset with valid token` | Password dapat diganti memakai token | Gagal | Token reset tidak pernah dibuat karena lookup email gagal |
| 16 | `tests/Feature/Auth/PasswordUpdateTest.php` | `password can be updated` | User dapat mengganti password | Lulus |  |
| 17 | `tests/Feature/Auth/PasswordUpdateTest.php` | `correct password must be provided to update password` | Password lama wajib benar | Lulus |  |
| 18 | `tests/Feature/Auth/RegistrationTest.php` | `registration screen is disabled` | Route GET registrasi tidak tersedia | Lulus | Expected HTTP 404 |
| 19 | `tests/Feature/Auth/RegistrationTest.php` | `registration submission redirects to login without authenticating` | POST registrasi tidak membuat akun | Lulus | Redirect ke login |
| 20 | `tests/Feature/AuthorizationTest.php` | `guest is redirected from authenticated routes` | Guest tidak dapat membuka dashboard/notifikasi | Lulus |  |
| 21 | `tests/Feature/AuthorizationTest.php` | `regular user cannot view admin data or manage go check` | User biasa ditolak dari route khusus | Lulus | HTTP 403 |
| 22 | `tests/Feature/AuthorizationTest.php` | `five r team can view admin data but cannot manage go check` | Role tim 5R hanya dapat melihat data admin | Lulus |  |
| 23 | `tests/Feature/AuthorizationTest.php` | `five r ketua can manage go check` | Ketua 5R dapat mengelola Go Check | Lulus |  |
| 24 | `tests/Feature/ExampleTest.php` | `the application returns a successful response` | Smoke test response aplikasi | Lulus |  |
| 25 | `tests/Feature/GoOfferIndexTest.php` | `index filters by search and keeps query in pagination links` | Filter pencarian dan query pagination Go Offer | Lulus |  |
| 26 | `tests/Feature/NotificationTest.php` | `user only sees their own notifications` | Isolasi daftar notifikasi antar user | Lulus |  |
| 27 | `tests/Feature/NotificationTest.php` | `mark all as read only updates the authenticated user` | Tandai semua hanya milik user aktif | Lulus |  |
| 28 | `tests/Feature/NotificationTest.php` | `user cannot mark another users notification as read` | Proteksi update notifikasi user lain | Lulus | HTTP 404 |
| 29 | `tests/Feature/ProfileTest.php` | `profile page is displayed` | Halaman profile | Lulus |  |
| 30 | `tests/Feature/ProfileTest.php` | `profile information can be updated` | Update nama, NPP, dan bagian | Lulus |  |
| 31 | `tests/Feature/ProfileTest.php` | `email verification status is unchanged when the email address is unchanged` | Update profile tanpa mengubah identitas | Lulus | Test lama dipertahankan, assertion disesuaikan ke NPP |
| 32 | `tests/Feature/ProfileTest.php` | `user can delete their account` | User dapat menghapus akun sendiri | Lulus |  |
| 33 | `tests/Feature/ProfileTest.php` | `correct password must be provided to delete account` | Hapus akun membutuhkan password benar | Lulus |  |

### Detail Playwright E2E

| No. | File | Test case | Verifikasi | Hasil |
| --- | --- | --- | --- | --- |
| 1 | `tests/e2e/smoke.spec.js` | `guest can open the login page` | Halaman login dan input NPP tampil | Lulus |
| 2 | `tests/e2e/smoke.spec.js` | `guest is redirected to login from the dashboard` | Guest diarahkan ke login saat membuka dashboard | Lulus |
| 3 | `tests/e2e/smoke.spec.js` | `registration remains disabled` | Route registrasi mengembalikan HTTP 404 | Lulus |

### Detail Build Frontend

| Pemeriksaan | Hasil |
| --- | --- |
| Transformasi modul Vue/JS | Berhasil |
| Pembuatan asset production | Berhasil |
| Output build | `public/build/` |
| Error build | Tidak ada |

## Kegagalan yang Diketahui

Enam kegagalan berasal dari test auth lama untuk email verification dan password reset:

- `UserFactory::unverified()` tidak relevan lagi karena aplikasi tidak menyimpan `email_verified_at`.
- Password reset masih menggunakan alamat email, sedangkan migration aplikasi menghapus kolom `email`.
- Email verification juga masih mengasumsikan kolom email dan kontrak verifikasi email Laravel.

Ini adalah ketidaksesuaian kontrak aplikasi yang perlu diputuskan sebelum suite dapat 100% hijau:

| Opsi | Perubahan | Dampak |
| --- | --- | --- |
| A. Tetap NPP-only | Hapus/nonaktifkan route email verification dan password reset, hapus test email lama, lalu sediakan reset password berbasis admin/NPP | Konsisten dengan schema saat ini |
| B. Pulihkan email | Pulihkan kolom `email` dan `email_verified_at`, sesuaikan model `User` dengan `MustVerifyEmail`, lalu pertahankan test email | Fitur email kembali tersedia, tetapi perlu migration dan konfigurasi mail |

Jangan mengklaim suite 100% lulus sebelum salah satu keputusan tersebut diimplementasikan.

## Artefak

- Laporan HTML Playwright tersedia di `playwright-report/index.html` setelah `npm run test:e2e`.
- Screenshot dan trace kegagalan Playwright tersedia di `test-results/`.
- Database test menggunakan database MySQL terpisah bernama `testing` yang dibuat oleh Docker Compose.

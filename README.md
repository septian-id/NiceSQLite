# NiceSQLite.php

`NiceSQLite.php` adalah sebuah kelas PHP tunggal yang berfungsi sebagai *wrapper* (pembungkus) untuk ekstensi PDO SQLite. Tujuannya adalah untuk menyederhanakan dan mengamankan interaksi dengan database SQLite dalam aplikasi PHP.

Kelas ini menyediakan antarmuka yang bersih dan intuitif untuk operasi database umum (CRUD), sekaligus menerapkan praktik keamanan modern secara default untuk melindungi dari serangan umum seperti SQL Injection.

## Informasi Pengembang

*   **Nama** : Septiana Harun
*   **E-Mail** : `septian.apps@gmail.com`
*   **Telepon**: `085117018997`
*   **Website**: https://niceportal.net

## Fitur Utama

*   **Antarmuka CRUD Sederhana**: Metode intuitif seperti `insert()`, `select()`, `update()`, dan `delete()`.
*   **Aman Secara Default**: Menggunakan **Prepared Statements** secara internal untuk semua operasi data, memberikan perlindungan kuat terhadap SQL Injection.
*   **Pembuatan Direktori Otomatis**: Secara otomatis membuat direktori database jika belum ada.
*   **Generator ID Unik**: Termasuk metode `getUniqueId()` untuk membuat ID acak yang mudah dibaca, cocok untuk referensi publik.
*   **Fleksibilitas Query Kustom**: Metode `fetchAll()` dan `fetchOne()` untuk menjalankan query SQL yang lebih kompleks seperti `JOIN`.
*   **Helper Keamanan**: Termasuk metode `validateColumn()` untuk melakukan *whitelisting* nama kolom, mencegah SQL Injection pada klausa `ORDER BY`.
*   **Penanganan Error**: Menggunakan `PDOException` dan mencatat error ke log server tanpa membocorkan informasi sensitif ke pengguna.

## Persyaratan

*   PHP 7.2 atau lebih baru.
*   Ekstensi PHP `PDO`.
*   Ekstensi PHP `pdo_sqlite`.

## Instalasi

Cukup unduh file `NiceSQLite.php` dan sertakan (`require_once`) di dalam proyek PHP Anda.

```php
require_once 'path/to/NiceSQLite.php';
```

## Panduan Cepat

Berikut adalah contoh lengkap penggunaan dasar kelas `NiceSQLite`.

```php
<?php

require_once 'NiceSQLite.php';

// 1. Inisialisasi kelas dengan path ke file database Anda
$db = new NiceSQLite('database/aplikasi_saya.sqlite');

// 2. (Opsional) Buat tabel jika belum ada menggunakan exec()
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    )
");

// 3. Menyisipkan data baru menggunakan insert() dan getUniqueId()
echo "Menyisipkan data baru...\n";
$newUserId = $db->insert('users', [
    'user_id' => $db->getUniqueId(8), // Membuat ID publik yang unik
    'name' => 'Budi Santoso',
    'email' => 'budi.s@example.com'
]);

if ($newUserId) {
    echo "Berhasil menyisipkan user dengan primary key ID: " . $newUserId . "\n";
}

// 4. Membaca data menggunakan select()
// Ambil user berdasarkan primary key ID
echo "\nMengambil user dengan ID " . $newUserId . "...\n";
$user = $db->select('users', $newUserId);
print_r($user);

// Ambil semua user
echo "\nMengambil semua user...\n";
$allUsers = $db->select('users'); // atau $db->select_all('users');
print_r($allUsers);

// 5. Memperbarui data menggunakan update()
echo "\nMemperbarui nama user dengan ID " . $newUserId . "...\n";
$success = $db->update('users', ['name' => 'Budi Hartono'], $newUserId);
if ($success) {
    echo "Update berhasil.\n";
    $updatedUser = $db->select('users', $newUserId);
    print_r($updatedUser);
}

// 6. Menghapus data menggunakan delete()
echo "\nMenghapus user dengan ID " . $newUserId . "...\n";
$deletedRows = $db->delete('users', $newUserId);
if ($deletedRows > 0) {
    echo "Berhasil menghapus " . $deletedRows . " baris.\n";
}

?>
```

## Dokumentasi API

### `__construct(string $dbPath)`
Membuat koneksi ke database.
*   `$dbPath`: Path ke file `.sqlite` Anda.

### `getUniqueId(int $length = 10)`
Menghasilkan ID string yang unik dan acak, cocok untuk ID publik atau referensi yang mudah dibaca.
*   `$length`: Panjang ID yang diinginkan (default: 10).
*   **Mengembalikan**: Sebuah string ID unik yang dihasilkan.

```php
$new_ref = $db->getUniqueId(8); // Menghasilkan sesuatu seperti 'N5P8YJ2A'
$db->insert('users', [
    'user_id' => $new_ref,
    'name' => 'Andi'
]);
```
### `insert(string $table, array $data)`
Menyisipkan satu baris data ke dalam tabel.
*   `$table`: Nama tabel.
*   `$data`: Array asosiatif `['kolom' => 'nilai']`.
*   **Mengembalikan**: ID dari baris yang baru dibuat (`lastInsertId`), atau `false` jika gagal.

### `select(string $table, $condition = null)`
Mengambil data dari tabel dengan berbagai kondisi.
*   `$table`: Nama tabel.
*   `$condition`:
    *   `null` (default): Mengambil semua baris dari tabel.
    *   `integer`: Mengambil satu baris berdasarkan `id` (primary key).
    *   `array`: Mengambil baris yang cocok dengan kriteria `['kolom' => 'nilai', ...]`.
*   **Mengembalikan**: Sebuah array hasil, atau `false` jika pencarian berdasarkan ID tidak ditemukan.

```php
$user_5 = $db->select('users', 5); // Mengambil user dengan id = 5
$admins = $db->select('users', ['role' => 'admin', 'status' => 'active']); // Mengambil semua admin yang aktif
$all_users = $db->select('users'); // Mengambil semua user
```

### `update(string $table, array $data, int $id)`
Memperbarui satu baris data berdasarkan ID.
*   `$table`: Nama tabel.
*   `$data`: Array asosiatif data baru `['kolom' => 'nilai']`.
*   `$id`: ID dari baris yang akan diubah.
*   **Mengembalikan**: `true` jika ada baris yang terpengaruh, `false` jika tidak.

### `delete(string $table, int $id)`
Menghapus satu baris data berdasarkan ID.
*   `$table`: Nama tabel.
*   `$id`: ID dari baris yang akan dihapus.
*   **Mengembalikan**: Jumlah baris yang dihapus (biasanya `1` atau `0`).

### `fetchAll(string $sql, array $params = [])`
Menjalankan query SQL kustom dan mengembalikan semua hasil. Berguna untuk `JOIN` atau query kompleks lainnya.

> **Peringatan Keamanan**: Metode ini mengeksekusi SQL mentah. Pastikan semua bagian dari string `$sql` yang berasal dari input pengguna (seperti nama kolom untuk `ORDER BY`) telah divalidasi atau di-*whitelist*. Selalu gunakan parameter binding (`$params`) untuk semua **nilai data**.

```php
$sql = "SELECT p.*, u.name FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = :status";
$published_posts = $db->fetchAll($sql, [':status' => 'published']);
```

### `fetchOne(string $sql, array $params = [])`
Sama seperti `fetchAll`, tetapi hanya mengembalikan satu baris pertama dari hasil query.

### `exec(string $sql)`
Menjalankan perintah SQL yang tidak mengembalikan data, seperti `CREATE TABLE`, `ALTER TABLE`, atau `DROP TABLE`.

> **Peringatan Keamanan**: Gunakan metode ini dengan sangat hati-hati. Jangan pernah membangun string `$sql` dari input pengguna tanpa sanitasi yang sangat ketat.

### `validateColumn(string $column, array $allowedColumns)`
Helper keamanan untuk memvalidasi nama kolom terhadap daftar yang diizinkan (*whitelist*). Sangat penting untuk mencegah SQL Injection pada klausa dinamis seperti `ORDER BY`.
*   **Mengembalikan**: Nama kolom yang sudah di-*quote* dengan aman jika valid.
*   **Melempar**: `InvalidArgumentException` jika kolom tidak ada dalam *whitelist*.

```php
$allowed = ['name', 'email', 'created_at'];
$sort_by = $_GET['sort'] ?? 'name';

try {
    $safe_column = $db->validateColumn($sort_by, $allowed);
    $sql = "SELECT * FROM users ORDER BY {$safe_column} ASC";
    $users = $db->fetchAll($sql);
} catch (\InvalidArgumentException $e) {
    die("Error: Kolom sorting tidak valid.");
}
```

## Praktik Keamanan Terbaik

Menggunakan kelas ini adalah langkah pertama. Untuk aplikasi yang benar-benar aman, terapkan praktik berikut dalam logika aplikasi Anda.

### 1. SQL Injection

*   **Nilai Data**: Kelas ini sudah melindungi Anda saat menggunakan metode `insert`, `select` (dengan array), `update`, dan `delete`.
*   **Struktur Query (Identifier)**: Saat membangun query kustom (misal, untuk `ORDER BY`), selalu gunakan metode `validateColumn()` untuk mem-validasi nama kolom yang berasal dari input pengguna.

### 2. Cross-Site Scripting (XSS)

Kelas ini tidak melindungi dari XSS. Anda bertanggung jawab untuk melakukan "escaping" pada output. Selalu gunakan `htmlspecialchars()` saat menampilkan data dari database ke halaman HTML.

**Prinsip: Filter on Input, Escape on Output.**

```php
<?php
$user = $db->select('users', 1);

// BENAR: Data di-escape sebelum ditampilkan
echo '<h1>' . htmlspecialchars($user['name']) . '</h1>';

// SALAH (RENTAN XSS): Data langsung ditampilkan
// echo '<h1>' . $user['name'] . '</h1>';
?>
```

### 3. Cross-Site Request Forgery (CSRF)

Untuk setiap form yang melakukan aksi perubahan data (`POST`, `PUT`, `DELETE`), gunakan **token anti-CSRF**.

1.  Buat token acak dan simpan di `$_SESSION`.
2.  Sertakan token tersebut di dalam form sebagai input tersembunyi.
3.  Saat memproses form, verifikasi bahwa token yang dikirim sama dengan yang ada di sesi.

### 4. Insecure Direct Object Reference (IDOR)

Jangan pernah mengasumsikan pengguna berhak mengakses sebuah data hanya karena mereka tahu ID-nya. Saat mengambil atau memodifikasi data, selalu tambahkan kondisi `WHERE` untuk memeriksa kepemilikan.

```php
// SALAH: Rentan IDOR
// $db->update('notes', ['content' => $new_content], $note_id);

// BENAR: Memverifikasi kepemilikan
$note = $db->select('notes', $note_id);
if ($note && $note['user_id'] === $_SESSION['user_id']) {
    // Lanjutkan operasi update...
} else {
    // Tolak akses!
}
```

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT.

<?php

/**
 * Kelas untuk mengelola database SQLite.
 *
 * Menyediakan antarmuka sederhana untuk operasi CRUD (Create, Read, Update, Delete).
 *
 * @author  Septiana Harun <septian.apps@gmail.com> (085117018997)
 * @link    https://niceportal.net
 * @version 1.0.0
 */
class NiceSQLite
{
    /**
     * @var PDO Objek koneksi PDO ke database SQLite.
     */
    private $pdo;

    /**
     * @var string Path ke file database SQLite.
     */
    private $dbPath;

    /**
     * Konstruktor kelas NiceSQLite.
     * Membuat koneksi ke database SQLite. Jika file database tidak ada, file tersebut akan dibuat.
     *
     * @param string $dbPath Path ke file database (misal: 'database/my_app.sqlite').
     */
    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
        $dir = dirname($this->dbPath);

        // Buat direktori jika belum ada
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            // Buat atau buka koneksi ke database
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            // Atur mode error untuk melempar exception jika terjadi kesalahan
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Atur mode fetch default ke associative array (seperti objek di JavaScript)
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Catat error detail ke log server
            error_log("Koneksi ke database gagal: " . $e->getMessage());
            // Tampilkan pesan generik dan hentikan eksekusi
            die("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
        }
    }

    /**
     * Menyisipkan data baru ke dalam sebuah tabel.
     *
     * @param string $table Nama tabel.
     * @param array $data Data yang akan disisipkan dalam bentuk associative array [kolom => nilai].
     * @return string|false ID dari baris yang baru disisipkan, atau false jika gagal.
     */
    public function insert(string $table, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $columns = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));

        $safeTable = $this->quoteIdentifier($table);
        $sql = "INSERT INTO {$safeTable} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Gagal insert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memilih data dari tabel berdasarkan kondisi.
     *
     * @param string $table Nama tabel.
     * @param array|int|null $condition Kondisi pencarian.
     *                                  - null: Ambil semua data (select_all).
     *                                  - int: Dianggap sebagai pencarian berdasarkan ID (primary key).
     *                                  - array: Kondisi WHERE [kolom => nilai].
     * @return array|false Data yang cocok, atau false jika tidak ditemukan (khusus pencarian by ID).
     */
    public function select(string $table, $condition = null)
    {
        // Jika kondisi null, panggil select_all
        if ($condition === null) {
            return $this->select_all($table);
        }

        $sql = "SELECT * FROM " . $this->quoteIdentifier($table);
        $params = [];

        if (is_int($condition) || is_string($condition) && ctype_digit($condition)) {
            // Pencarian berdasarkan ID (asumsi nama primary key adalah 'id')
            $sql .= " WHERE id = :id";
            $params = [':id' => $condition];
            // Menggunakan fetch() karena hanya mengharapkan satu hasil
            return $this->fetchOne($sql, $params);
        } elseif (is_array($condition) && !empty($condition)) {
            // Pencarian berdasarkan kriteria array
            $whereClauses = [];
            foreach ($condition as $key => $value) {
                $whereClauses[] = $this->quoteIdentifier($key) . " = :{$key}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
            $params = $condition;
            // Menggunakan fetchAll() karena bisa jadi ada banyak hasil
            return $this->fetchAll($sql, $params);
        }
        
        // Jika kondisi tidak valid, kembalikan array kosong
        return [];
    }

    /**
     * Memilih semua data dari sebuah tabel.
     *
     * @param string $table Nama tabel.
     * @return array Semua data dalam tabel.
     */
    public function select_all(string $table): array
    {
        return $this->fetchAll("SELECT * FROM " . $this->quoteIdentifier($table));
    }

    /**
     * Memperbarui data dalam tabel berdasarkan ID.
     *
     * @param string $table Nama tabel.
     * @param array $data Data baru yang akan diupdate [kolom => nilai].
     * @param int $id ID dari baris yang akan diperbarui.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function update(string $table, array $data, int $id): bool
    {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = $this->quoteIdentifier($key) . " = :{$key}";
        }
        
        $safeTable = $this->quoteIdentifier($table);
        $sql = "UPDATE {$safeTable} SET " . implode(', ', $setClauses) . " WHERE id = :id";

        // Tambahkan id ke array data untuk binding parameter
        $data['id'] = $id;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt->rowCount() > 0; // Mengembalikan true jika ada baris yang terpengaruh
        } catch (PDOException $e) {
            error_log("Gagal update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus data dari tabel berdasarkan ID.
     *
     * @param string $table Nama tabel.
     * @param int $id ID dari baris yang akan dihapus.
     * @return int Jumlah baris yang berhasil dihapus.
     */
    public function delete(string $table, int $id): int
    {
        $safeTable = $this->quoteIdentifier($table);
        $sql = "DELETE FROM {$safeTable} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Gagal delete: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Menjalankan query kustom dan mengambil semua hasilnya.
     * Berguna untuk query yang lebih kompleks (JOIN, GROUP BY, dll).
     *
     * @param string $sql Query SQL.
     * @param array $params Parameter untuk prepared statement.
     * @return array Hasil query.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Gagal fetchAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menjalankan query kustom dan mengambil satu baris hasil.
     *
     * @param string $sql Query SQL.
     * @param array $params Parameter untuk prepared statement.
     * @return mixed Satu baris hasil, atau false jika tidak ada hasil.
     *
     * @warning Metode ini mengeksekusi SQL mentah. Pastikan semua bagian dari string $sql yang berasal dari input pengguna telah divalidasi atau di-whitelist. Gunakan parameter binding ($params) untuk semua nilai data.
     */
    public function fetchOne(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Gagal fetchOne: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menjalankan query kustom tanpa mengambil hasil (misal: CREATE TABLE, DROP TABLE).
     *
     * @param string $sql Query SQL.
     * @return bool True jika berhasil, false jika gagal.
     *
     * @warning Metode ini sangat berbahaya jika string $sql dibangun dari input pengguna tanpa sanitasi yang ketat, karena dapat menjalankan perintah DDL (Data Definition Language) seperti DROP TABLE. Gunakan dengan sangat hati-hati.
     */
    public function exec(string $sql): bool
    {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("Gagal exec: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Meng-quote sebuah identifier (nama tabel atau kolom) untuk mencegah SQL injection.
     * SQLite menggunakan backtick (`) atau double quote (") untuk meng-quote identifier.
     *
     * @param string $identifier Nama yang akan di-quote.
     * @return string Identifier yang sudah di-quote.
     */
    private function quoteIdentifier(string $identifier): string
    {
        // Hapus karakter quote yang mungkin sudah ada untuk menghindari double quoting
        $identifier = str_replace('`', '', $identifier);
        // Bungkus dengan backtick
        return "`" . $identifier . "`";
    }

    /**
     * Memvalidasi nama kolom terhadap daftar kolom yang diizinkan (whitelist).
     * Jika valid, nama kolom akan di-quote dengan aman. Jika tidak, akan melempar Exception.
     *
     * @param string $column Nama kolom yang akan divalidasi (misal: dari $_GET).
     * @param array $allowedColumns Daftar kolom yang diizinkan (misal: ['id', 'name', 'created_at']).
     * @return string Nama kolom yang sudah di-quote dan aman digunakan dalam query.
     * @throws \InvalidArgumentException Jika kolom tidak ada dalam whitelist.
     */
    public function validateColumn(string $column, array $allowedColumns): string
    {
        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Nama kolom '{$column}' tidak valid atau tidak diizinkan.");
        }
        return $this->quoteIdentifier($column);
    }
}

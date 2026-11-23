<?php
// Mengimpor file koneksi ke database
require_once 'config/koneksi.php';

// Membuat hash password baru (bcrypt/argon2 mengikuti PASSWORD_DEFAULT)
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Mengecek password dengan dukungan data lama:
// 1. Hash modern (bcrypt/argon2) dari password_hash()
// 2. Hash SHA1 (40 karakter hex) dari versi lama aplikasi
// 3. Password plaintext dari data seed lama
function verifyPassword($input, $stored)
{
    if ($stored === '') {
        return false;
    }

    if (password_verify($input, $stored)) {
        return true;
    }

    if (strlen($stored) === 40 && ctype_xdigit($stored)) {
        return hash_equals($stored, sha1($input));
    }

    return hash_equals($stored, $input);
}

// Mengecek apakah hash password perlu di-upgrade ke algoritma saat ini
function passwordNeedsRehash($stored)
{
    return password_needs_rehash($stored, PASSWORD_DEFAULT);
}

// Membuat atau mengambil token CSRF milik session saat ini
function csrfToken()
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Hidden input token untuk ditempel di dalam form POST
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// Memvalidasi token CSRF dari POST; false jika kosong atau tidak cocok
function csrfValid()
{
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// Normalisasi email/IP sebelum dipakai di query percobaan login
function loginAttemptIdentity($email, $ip)
{
    return [
        'email' => mb_substr((string) $email, 0, 100),
        'ip'    => substr((string) $ip, 0, 45),
    ];
}

// Menghitung percobaan login gagal dalam jendela waktu tertentu
function loginAttemptCount($config, $email, $ip, $minutes = 15)
{
    $minutes = max(1, (int) $minutes);
    $data    = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    $query = mysqli_query($config, "SELECT COUNT(*) AS total FROM login_attempt WHERE email='$emailSafe' AND ip_address='$ipSafe' AND attempted_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE)");
    $row   = $query ? mysqli_fetch_assoc($query) : null;

    return (int) ($row['total'] ?? 0);
}

// Mencatat satu percobaan login gagal
function loginAttemptAdd($config, $email, $ip)
{
    $data      = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    mysqli_query($config, "INSERT INTO login_attempt (email, ip_address, attempted_at) VALUES ('$emailSafe', '$ipSafe', NOW())");
}

// Menghapus riwayat percobaan setelah login berhasil
function loginAttemptClear($config, $email, $ip)
{
    $data      = loginAttemptIdentity($email, $ip);
    $emailSafe = mysqli_real_escape_string($config, $data['email']);
    $ipSafe    = mysqli_real_escape_string($config, $data['ip']);

    mysqli_query($config, "DELETE FROM login_attempt WHERE email='$emailSafe' AND ip_address='$ipSafe'");
}

// Membersihkan catatan lama agar tabel tidak membengkak
function loginAttemptCleanup($config, $days = 1)
{
    $days = max(1, (int) $days);

    mysqli_query($config, "DELETE FROM login_attempt WHERE attempted_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
}

// Status pembayaran: 0 belum bayar, 1 lunas
function labelStatusBayar($status)
{
    return (int) $status === 1 ? 'Lunas' : 'Belum Bayar';
}

function badgeStatusBayar($status)
{
    if ((int) $status === 1) {
        return '<span class="badge bg-success">Lunas</span>';
    }

    return '<span class="badge bg-danger">Belum Bayar</span>';
}

// Tahap cucian: 0 antrian, 1 dicuci, 2 siap diambil, 3 sudah diambil
function labelStatusCucian($status)
{
    $daftar = [0 => 'Antrian', 1 => 'Dicuci', 2 => 'Siap Diambil', 3 => 'Sudah Diambil'];

    return $daftar[(int) $status] ?? $daftar[0];
}

function badgeStatusCucian($status)
{
    $status = (int) $status;
    $kelas  = [0 => 'bg-secondary', 1 => 'bg-info text-dark', 2 => 'bg-primary', 3 => 'bg-success'];

    return '<span class="badge ' . ($kelas[$status] ?? 'bg-secondary') . '">' . labelStatusCucian($status) . '</span>';
}

// Format tanggal yang dipakai halaman detail dan struk
function tanggal($date)
{
    $waktu = strtotime((string) $date);

    return $waktu ? date('d F Y', $waktu) : '-';
}

function formatTanggalIndo($date)
{
    return tanggal($date);
}

// Format ringkas untuk tabel: 22/11/2026
function formatTanggalSingkat($date)
{
    $waktu = strtotime((string) $date);

    return $waktu ? date('d/m/Y', $waktu) : '-';
}

// Validasi tanggal Y-m-d yang benar-benar ada di kalender (menolak 2026-02-30)
function tanggalValid($value)
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $d = DateTime::createFromFormat('Y-m-d', $value);

    return $d && $d->format('Y-m-d') === $value;
}

// Format rupiah untuk ringkasan dan tabel laporan
function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

// Qty tersimpan dalam gram; tampilkan sebagai kg tanpa nol berekor
function formatKg($gram)
{
    $teks = number_format(((float) $gram) / 1000, 2, ',', '.');
    $teks = rtrim(rtrim($teks, '0'), ',');

    return $teks === '' ? '0' : $teks;
}

// Membaca filter laporan dari query string.
// Default: awal bulan berjalan sampai hari ini.
function filterLaporan(?array $sumber = null)
{
    $sumber = $sumber ?? $_GET;

    $date    = is_string($sumber['date'] ?? null) ? trim($sumber['date']) : '';
    $endDate = is_string($sumber['end_date'] ?? null) ? trim($sumber['end_date']) : '';

    if (!tanggalValid($date)) {
        $date = date('Y-m-01');
    }

    if (!tanggalValid($endDate)) {
        $endDate = date('Y-m-d');
    }

    // Rentang terbalik ditukar supaya hasilnya tetap masuk akal
    if ($date > $endDate) {
        [$date, $endDate] = [$endDate, $date];
    }

    $status = is_string($sumber['status'] ?? null) ? $sumber['status'] : '';
    if (!in_array($status, ['0', '1'], true)) {
        $status = '';
    }

    $cucian = is_string($sumber['cucian'] ?? null) ? $sumber['cucian'] : '';
    if (!in_array($cucian, ['0', '1', '2', '3'], true)) {
        $cucian = '';
    }

    return [
        'date'     => $date,
        'end_date' => $endDate,
        'status'   => $status,
        'cucian'   => $cucian,
    ];
}

// WHERE laporan yang sudah dibersihkan; dipakai halaman, CSV, dan cetak
function laporanWhere($config, array $filter)
{
    $dateSafe = mysqli_real_escape_string($config, $filter['date']);
    $endSafe  = mysqli_real_escape_string($config, $filter['end_date']);

    $where = "trans_order.deleted_at IS NULL AND trans_order.order_date BETWEEN '$dateSafe' AND '$endSafe'";

    if ($filter['status'] !== '') {
        $where .= ' AND trans_order.order_status = ' . (int) $filter['status'];
    }

    if ($filter['cucian'] !== '') {
        $where .= ' AND trans_order.laundry_status = ' . (int) $filter['cucian'];
    }

    return $where;
}

// Query string filter untuk tautan ekspor dan cetak
function queryLaporan(array $filter)
{
    $data = [
        'date'     => $filter['date'],
        'end_date' => $filter['end_date'],
    ];

    if ($filter['status'] !== '') {
        $data['status'] = $filter['status'];
    }

    if ($filter['cucian'] !== '') {
        $data['cucian'] = $filter['cucian'];
    }

    return http_build_query($data);
}

// Ringkasan filter dalam bahasa manusia
function keteranganLaporan(array $filter)
{
    $teks = formatTanggalIndo($filter['date']) . ' sampai ' . formatTanggalIndo($filter['end_date']);

    if ($filter['status'] !== '') {
        $teks .= ' | ' . labelStatusBayar($filter['status']);
    }

    if ($filter['cucian'] !== '') {
        $teks .= ' | ' . labelStatusCucian($filter['cucian']);
    }

    return $teks;
}

// Ringkasan angka laporan: jumlah order, pendapatan lunas, piutang, total kg
function laporanRingkasan($config, $where)
{
    $query = mysqli_query($config, "
        SELECT
            COUNT(*) AS total_order,
            COALESCE(SUM(CASE WHEN trans_order.order_status = 1 THEN trans_order.total ELSE 0 END), 0) AS pendapatan,
            COALESCE(SUM(CASE WHEN trans_order.order_status = 0 THEN trans_order.total ELSE 0 END), 0) AS piutang
        FROM trans_order
        WHERE $where
    ");
    $row = $query ? mysqli_fetch_assoc($query) : null;

    $queryGram = mysqli_query($config, "
        SELECT COALESCE(SUM(trans_order_detail.qty), 0) AS gram
        FROM trans_order_detail
        INNER JOIN trans_order ON trans_order.id = trans_order_detail.id_order
        WHERE $where
    ");
    $gram = $queryGram ? mysqli_fetch_assoc($queryGram) : null;

    return [
        'total_order' => (int) ($row['total_order'] ?? 0),
        'pendapatan'  => (float) ($row['pendapatan'] ?? 0),
        'piutang'     => (float) ($row['piutang'] ?? 0),
        'gram'        => (int) round((float) ($gram['gram'] ?? 0)),
    ];
}

// Daftar order pada rentang laporan, urut dari yang paling lama
function laporanOrder($config, $where)
{
    $query = mysqli_query($config, "
        SELECT
            trans_order.id,
            trans_order.order_code,
            trans_order.order_date,
            trans_order.order_end_date,
            trans_order.total,
            trans_order.order_status,
            trans_order.laundry_status,
            customer.customer_name,
            COALESCE((SELECT SUM(qty) FROM trans_order_detail WHERE id_order = trans_order.id), 0) AS gram
        FROM trans_order
        LEFT JOIN customer ON customer.id = trans_order.id_customer
        WHERE $where
        ORDER BY trans_order.order_date ASC, trans_order.id ASC
    ");

    return $query ? mysqli_fetch_all($query, MYSQLI_ASSOC) : [];
}

// Rekap qty dan subtotal per jenis layanan
function laporanLayanan($config, $where)
{
    $query = mysqli_query($config, "
        SELECT
            COALESCE(type_of_service.service_name, '-') AS service_name,
            SUM(trans_order_detail.qty) AS gram,
            SUM(trans_order_detail.subtotal) AS subtotal
        FROM trans_order_detail
        INNER JOIN trans_order ON trans_order.id = trans_order_detail.id_order
        LEFT JOIN type_of_service ON type_of_service.id = trans_order_detail.id_service
        WHERE $where
        GROUP BY trans_order_detail.id_service, type_of_service.service_name
        ORDER BY subtotal DESC, service_name ASC
    ");

    return $query ? mysqli_fetch_all($query, MYSQLI_ASSOC) : [];
}

// Cegah CSV injection: sel teks yang diawali = + - @ diberi apostrof
function csvAman($nilai)
{
    $nilai = (string) $nilai;

    if ($nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@'], true)) {
        return "'" . $nilai;
    }

    return $nilai;
}

// Cucian lewat tenggat bila estimasi sudah lewat dan belum diambil
function cucianLewatTenggat($endDate, $laundryStatus)
{
    $waktu = strtotime((string) $endDate);

    if (!$waktu || (int) $laundryStatus >= 3) {
        return false;
    }

    return $waktu < strtotime(date('Y-m-d'));
}

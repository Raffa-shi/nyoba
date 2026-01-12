<?php
session_start();

// Daftar harga per tipe (array harga untuk masing-masing item)
// label: $prices -> menyimpan harga tiap nama item per kategori
$prices = [
    'nasi' => [
        'Nasi Putih' => 5000,
        'Nasi Goreng' => 10000
    ],
    'lauk' => [
        'Ayam Goreng' => 15000,
        'Ikan Bakar' => 20000,
        'Tempe' => 7000
    ],
    'minum' => [
        'Teh' => 3000,
        'Kopi' => 4000,
        'Air Mineral' => 2000
    ]
];

if (!isset($_SESSION['orders'])) {
    $_SESSION['orders'] = [];
}

// fungsi format rupiah
// label: rupiah($n) -> mengubah angka menjadi format "Rp x.xxx"
function rupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// aksi kosongkan semua pesanan
// label: ?action=clear -> menghapus semua data order pada session
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['orders'] = [];
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Proses form submit
$errors = [];
// label: POST submit_order -> validasi dan simpan order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    // ambil input (gunakan null coalescing untuk mencegah notice)
    $customer = trim($_POST['customer'] ?? '');
    $nasi_type = $_POST['nasi_type'] ?? '';
    $nasi_qty = (int)($_POST['nasi_qty'] ?? 0);
    $lauk_type = $_POST['lauk_type'] ?? '';
    $lauk_qty = (int)($_POST['lauk_qty'] ?? 0);
    $minum_type = $_POST['minum_type'] ?? '';
    $minum_qty = (int)($_POST['minum_qty'] ?? 0);
    $payment = (int)($_POST['payment'] ?? 0);

    // Validasi sederhana
    if ($customer === '') $errors[] = 'Nama pelanggan harus diisi.';
    if (!isset($prices['nasi'][$nasi_type])) $errors[] = 'Pilihan nasi tidak valid.';
    if ($nasi_qty < 0) $errors[] = 'Jumlah nasi tidak boleh negatif.';
    if (!isset($prices['lauk'][$lauk_type])) $errors[] = 'Pilihan lauk tidak valid.';
    if ($lauk_qty < 0) $errors[] = 'Jumlah lauk tidak boleh negatif.';
    if (!isset($prices['minum'][$minum_type])) $errors[] = 'Pilihan minuman tidak valid.';
    if ($minum_qty < 0) $errors[] = 'Jumlah minuman tidak boleh negatif.';

    if (empty($errors)) {
        // Hitung harga tiap kategori dan total
        $nasi_price = $prices['nasi'][$nasi_type] * $nasi_qty;
        $lauk_price = $prices['lauk'][$lauk_type] * $lauk_qty;
        $minum_price = $prices['minum'][$minum_type] * $minum_qty;
        $subtotal = $nasi_price + $lauk_price + $minum_price;
        $ppn = round($subtotal * 0.10); // PPN 10%
        $total = $subtotal + $ppn;
        $change = $payment - $total;

        // Simpan order ke session
        $_SESSION['orders'][] = [
            'time' => date('Y-m-d H:i:s'),
            'customer' => $customer,
            'nasi_type' => $nasi_type,
            'nasi_qty' => $nasi_qty,
            'nasi_price' => $nasi_price,
            'lauk_type' => $lauk_type,
            'lauk_qty' => $lauk_qty,
            'lauk_price' => $lauk_price,
            'minum_type' => $minum_type,
            'minum_qty' => $minum_qty,
            'minum_price' => $minum_price,
            'subtotal' => $subtotal,
            'ppn' => $ppn,
            'total' => $total,
            'payment' => $payment,
            'change' => $change
        ];

        // redirect untuk menghindari resubmit form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Untuk mempertahankan nilai form saat validasi gagal, ambil dari POST
$old = function($k, $default = '') {
    return htmlspecialchars($_POST[$k] ?? $default);
};
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>restoran raffa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background:#007bff; color:#222; }
        .card { max-width: 900px; margin:0 auto; background:#fff; padding:18px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        form { display:grid; gap:12px; }
        .flex { display:flex; gap:12px; }
        .col { flex:1; min-width:0; }
        label { display:block; font-weight:600; margin-bottom:6px; }
        input[type="text"], input[type="number"], select { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
        .actions { display:flex; gap:12px; align-items:center; margin-top:8px; }
        button { padding:10px 14px; border:none; background:#007bff; color:#fff; border-radius:6px; cursor:pointer; }
        a.clear { color:#c0392b;     text-decoration:none; font-weight:600; }
        table { border-collapse: collapse; width:100%; margin-top:18px; }
        th, td { border:1px solid #381313ff; padding:10px; text-align:left; vertical-align:top; }
        th { background:#f7f7f7; font-weight:700; }
        .small { font-size:0.85em; color:#666; }
        .error { color:#a00; padding:8px; background:#fff0f0; border:1px solid #f2c2c2; border-radius:6px; margin-bottom:10px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Restoran Family</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <!-- Form input pesanan -->
    <form method="post" action="">
        <!-- Nama pelanggan -->
        <!-- label: customer -> isi nama pelanggan -->
        <div>
            <label for="customer">Nama Pelanggan</label>
            <input type="text" id="customer" name="customer" required value="<?php echo $old('customer'); ?>">
        </div>

        <div class="flex">
            <div class="col">
                <!-- Pilih nasi -->
                <!-- label: nasi_type -> memilih tipe nasi; nasi_qty -> jumlah -->
                <label for="nasi_type">Pilih Nasi</label>
                <select id="nasi_type" name="nasi_type">
                    <?php foreach ($prices['nasi'] as $k => $v): ?>
                        <option value="<?php echo htmlspecialchars($k); ?>" <?php echo (isset($_POST['nasi_type']) && $_POST['nasi_type'] === $k) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k . ' - ' . rupiah($v)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="nasi_qty" class="small">Jumlah Nasi</label>
                <input type="number" id="nasi_qty" name="nasi_qty" value="<?php echo $old('nasi_qty', '1'); ?>" min="0">
            </div>

            <div class="col">
                <!-- Pilih lauk -->
                <!-- label: lauk_type -> memilih tipe lauk; lauk_qty -> jumlah -->
                <label for="lauk_type">Pilih Lauk</label>
                <select id="lauk_type" name="lauk_type">
                    <?php foreach ($prices['lauk'] as $k => $v): ?>
                        <option value="<?php echo htmlspecialchars($k); ?>" <?php echo (isset($_POST['lauk_type']) && $_POST['lauk_type'] === $k) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k . ' - ' . rupiah($v)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="lauk_qty" class="small">Jumlah Lauk</label>
                <input type="number" id="lauk_qty" name="lauk_qty" value="<?php echo $old('lauk_qty', '1'); ?>" min="0">
            </div>

            <div class="col">
                <!-- Pilih minuman -->
                <!-- label: minum_type -> memilih tipe minuman; minum_qty -> jumlah -->
                <label for="minum_type">Pilih Minuman</label>
                <select id="minum_type" name="minum_type">
                    <?php foreach ($prices['minum'] as $k => $v): ?>
                        <option value="<?php echo htmlspecialchars($k); ?>" <?php echo (isset($_POST['minum_type']) && $_POST['minum_type'] === $k) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k . ' - ' . rupiah($v)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="minum_qty" class="small">Jumlah Minuman</label>
                <input type="number" id="minum_qty" name="minum_qty" value="<?php echo $old('minum_qty', '1'); ?>" min="0">
            </div>
        </div>

        <div class="flex">
            <div class="col">
                <!-- Pembayaran -->
                <!-- label: payment -> uang yang diterima dari pelanggan -->
                <label for="payment">Pembayaran (masukan jumlah saldo uang yang anda punya )</label>
                <input type="number" id="payment" name="payment" value="<?php echo $old('payment', '0'); ?>" min="0">
                <div class="small">PPN otomatis 10% dari subtotal. Kembalian dihitung dari pembayaran dikurangi total.</div>
            </div>

            <div style="display:flex; align-items:flex-end;">
                <div class="actions">
                    <button type="submit" name="submit_order">Simpan Pesanan</button>
                    <a class="clear" href="?action=clear">Kosongkan Semua Pesanan</a>
                </div>
            </div>
        </div>
    </form>

    <!-- Tabel pesanan -->
    <h3>Tabel Pesanan</h3>
    <?php if (empty($_SESSION['orders'])): ?>
        <div class="small">Belum ada pesanan.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <!-- label header -->
                    <th>Nasi</th>
                    <th>Lauk</th>
                    <th>Minuman</th>
                    <th>Subtotal</th>
                    <th>PPN (10%)</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                    <th>Kembalian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['orders'] as $i => $o): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo htmlspecialchars($o['time']); ?></td>
                        <td><?php echo htmlspecialchars($o['customer']); ?></td>

                        <!-- Isi kolom kategori hanya menampilkan nama tipe dan qty (sesuai permintaan)
                             label: setiap sel menampilkan "Tipe x Jumlah" dan harga kecil di bawah -->
                        <td>
                            <?php echo htmlspecialchars($o['nasi_type'] . ' x ' . $o['nasi_qty']); ?>
                            <div class="small"><?php echo rupiah($o['nasi_price']); ?></div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($o['lauk_type'] . ' x ' . $o['lauk_qty']); ?>
                            <div class="small"><?php echo rupiah($o['lauk_price']); ?></div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($o['minum_type'] . ' x ' . $o['minum_qty']); ?>
                            <div class="small"><?php echo rupiah($o['minum_price']); ?></div>
                        </td>

                        <td><?php echo rupiah($o['subtotal']); ?></td>
                        <td><?php echo rupiah($o['ppn']); ?></td>
                        <td><?php echo rupiah($o['total']); ?></td>
                        <td><?php echo rupiah($o['payment']); ?></td>
                        <td><?php echo rupiah($o['change']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
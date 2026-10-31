<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hapus akun {{ config('app.name') }}</title>
    <style>
        body {
            margin: 0 auto;
            max-width: 40rem;
            padding: 2rem 1rem 3rem;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #2b2330;
            background: #fffafc;
        }
        h1 { font-size: 1.6rem; line-height: 1.3; }
        h2 { font-size: 1.15rem; margin-top: 2rem; }
        a { color: #a8326e; }
        @media (prefers-color-scheme: dark) {
            body { color: #f3e9ef; background: #1d171b; }
            a { color: #f08cbf; }
        }
    </style>
</head>
<body>
    <h1>Hapus akun {{ config('app.name') }}</h1>
    <p>Kamu bisa menghapus akun {{ config('app.name') }} kapan saja. Semua data akunmu dihapus permanen dan tidak bisa dikembalikan.</p>
    <p>Sebelum menghapus, kamu bisa menyimpan salinan datamu lewat Pengaturan &gt; Ekspor data di app.</p>

    <h2>Dari app</h2>
    <ol>
        <li>Buka {{ config('app.name') }}, lalu masuk ke Pengaturan.</li>
        <li>Pilih Hapus akun.</li>
        <li>Masukkan kata sandimu, atau pilih akun Google yang tersambung, lalu konfirmasi.</li>
    </ol>

    @if (filled($supportEmail))
        <h2>Tanpa app</h2>
        <p>Kirim email dari alamat email akunmu ke <a href="mailto:{{ $supportEmail }}?subject=Hapus%20akun">{{ $supportEmail }}</a> dengan judul "Hapus akun". Kami menghapus akunmu paling lambat 3 &times; 24 jam setelah email diterima, lalu membalas emailmu.</p>
    @endif

    <h2>Yang dihapus</h2>
    <ul>
        <li>Akun dan profilmu, termasuk foto profil.</li>
        <li>Ruang Pribadi beserta semua tugas, tabungan, tanggal penting, perjalanan, kenangan, dan fotonya.</li>
        <li>Ruang Bersama beserta seluruh isi dan fotonya. Ruang itu ikut ditutup, dan isinya juga hilang dari akun pasanganmu.</li>
        <li>Token masuk dan token notifikasi, jadi semua HP-mu langsung keluar.</li>
    </ul>

    <h2>Yang masih tersimpan sementara</h2>
    <p>Salinan cadangan database tidak diubah. Datamu ikut hilang dari sana saat cadangan lama terhapus otomatis, paling lama 30 hari setelah akun dihapus.</p>
</body>
</html>

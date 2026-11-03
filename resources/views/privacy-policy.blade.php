<x-public-page :title="'Kebijakan privasi '.config('app.name')">
    <h1>Kebijakan privasi {{ config('app.name') }}</h1>
    <p>Berlaku sejak 3 November 2026.</p>
    <p>{{ config('app.name') }} adalah aplikasi untuk mengatur tugas, tabungan, rencana liburan, dan kenangan, sendiri atau berdua dengan pasangan. Halaman ini menjelaskan data apa yang kami simpan, untuk apa, siapa yang bisa melihatnya, dan hak kamu atas data itu.</p>

    <h2>Pengelola data</h2>
    <p>{{ config('app.name') }} dikelola oleh Iqbal Fahrozi, sebagai pengendali data pribadi menurut Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP).</p>
    @if (filled($supportEmail))
        <p>Pertanyaan, keluhan, dan permintaan soal datamu bisa dikirim ke <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. Kami menjawab paling lambat 3 &times; 24 jam.</p>
    @endif

    <h2>Data yang kami simpan</h2>
    <ul>
        <li><strong>Akun:</strong> nama, email, kata sandi (disimpan sebagai hash, bukan teks asli), ID akun Google kalau kamu masuk dengan Google, warna diri, zona waktu, mata uang pilihan, tanggal lahir kalau kamu mengisinya, dan pengaturan notifikasi.</li>
        <li><strong>Foto profil</strong> kalau kamu mengunggahnya. Server menyimpannya ulang tanpa metadata EXIF, termasuk lokasi foto.</li>
        <li><strong>Isi yang kamu buat:</strong> daftar dan tugas, target dan catatan tabungan beserta nominalnya, tanggal penting, perjalanan, kenangan beserta nama tempat dan titik peta yang kamu pasang, foto kenangan (juga tanpa metadata), komentar, dan reaksi.</li>
        <li><strong>Perangkat:</strong> token notifikasi Firebase untuk setiap HP, jenis platformnya, dan token masuk untuk setiap HP.</li>
        <li><strong>Catatan teknis:</strong> alamat IP dan waktu request, untuk membatasi percobaan masuk yang berlebihan dan memperbaiki gangguan.</li>
    </ul>
    <p>Kami tidak memakai lokasi GPS (app tidak meminta izin lokasi), tidak membaca kontak, tidak menampilkan iklan, dan tidak memakai alat analytics.</p>

    <h2>Untuk apa data dipakai</h2>
    <ul>
        <li>Menyimpan datamu dan menyinkronkannya ke semua HP-mu dan ke HP pasanganmu di Ruang Bersama.</li>
        <li>Mengirim notifikasi pengingat dan aktivitas pasangan, sesuai pengaturan notifikasimu.</li>
        <li>Menjaga keamanan akun, misalnya membatasi percobaan masuk.</li>
        <li>Membantu kamu saat meminta bantuan, misalnya mengatur ulang kata sandi atau menghapus akun.</li>
    </ul>
    <p>Kami tidak menjual datamu, tidak memakainya untuk iklan, dan tidak membuat profil tentang kamu.</p>

    <h2>Dasar pemrosesan</h2>
    <p>Kami memproses datamu untuk menjalankan layanan yang kamu setujui saat mendaftar, sesuai <a href="{{ route('terms') }}">Syarat layanan</a>, dan untuk kepentingan yang sah menjaga keamanan layanan. Notifikasi bisa kamu matikan kapan saja di Pengaturan.</p>

    <h2>Siapa yang bisa melihat datamu</h2>
    <ul>
        <li><strong>Kamu:</strong> semua isi Ruang Pribadi dan Ruang Bersama-mu.</li>
        <li><strong>Pasanganmu:</strong> semua isi Ruang Bersama, serta nama, warna, foto profil, dan tanggal lahirmu. Isi Ruang Pribadi-mu tidak pernah terlihat oleh pasangan.</li>
        <li><strong>Pengelola:</strong> hanya data akun yang dibutuhkan untuk bantuan, yaitu nama, email, ruang beserta anggotanya, kode undangan, dan token perangkat. Isi tugas, tabungan, dan kenanganmu tidak ditampilkan di panel admin.</li>
    </ul>

    <h2>Penyedia layanan yang kami pakai</h2>
    <ul>
        <li><strong>Cloudflare</strong> meneruskan semua koneksi ke server, menyimpan foto profil dan foto kenangan, dan menyimpan cadangan database.</li>
        <li><strong>Google</strong> mengirim notifikasi lewat Firebase Cloud Messaging, termasuk isinya seperti judul tugas atau nominal setoran. Kalau kamu masuk dengan Google, Google juga mengirimkan nama, email, dan ID akun Google-mu kepada kami.</li>
        <li><strong>OpenStreetMap</strong> menyediakan peta dan pencarian tempat. Peta dimuat langsung oleh HP-mu, jadi server OpenStreetMap menerima alamat IP HP dan bagian peta yang dibuka. Pencarian tempat lewat server kami, dan hanya kata kuncinya yang dikirim.</li>
        <li><strong>Resend</strong> mengirim email berisi kode verifikasi, kode atur ulang kata sandi, kode ganti email, dan pemberitahuan keamanan akun, jadi menerima alamat emailmu dan isi email itu.</li>
        <li><strong>Penyedia server</strong> tempat aplikasi dan database berjalan.</li>
    </ul>
    <p>Penyedia ini bisa memproses data di luar Indonesia. Kami hanya memberi mereka data yang dibutuhkan untuk tugasnya.</p>

    <h2>Berapa lama data disimpan</h2>
    <ul>
        <li>Data akun dan isinya disimpan selama akunmu ada.</li>
        <li>Yang kamu hapus di app ditandai terhapus dan disimpan 30 hari sebagai catatan sinkron, supaya HP lain ikut menghapusnya, lalu dihapus permanen. Foto yang dihapus langsung dibuang dari penyimpanan.</li>
        <li>Kode verifikasi, kode atur ulang kata sandi, dan kode ganti email disimpan sebagai hash, lalu dihapus setelah dipakai atau paling lama sehari setelah kedaluwarsa. Alamat email baru yang menunggu kode ganti email disimpan bersama kodenya dan ikut terhapus.</li>
        <li>Ruang Bersama yang ditutup karena salah satu keluar disimpan 30 hari, supaya kembali utuh kalau kalian bergabung lagi, lalu dihapus permanen.</li>
        <li>Saat akun dihapus, semua datanya langsung dihapus permanen. Salinan di cadangan database ikut hilang paling lama 30 hari kemudian.</li>
        <li>Catatan teknis seperti alamat IP hanya disimpan selama dibutuhkan untuk keamanan dan memperbaiki gangguan. Notifikasi yang gagal terkirim, beserta isinya, dihapus paling lama 30 hari kemudian.</li>
    </ul>

    <h2>Hak kamu</h2>
    <ul>
        <li><strong>Mengakses dan menyalin data:</strong> lewat Pengaturan &gt; Ekspor data di app, sebagai file JSON, atau ZIP berisi file itu dan semua fotomu.</li>
        <li><strong>Memperbaiki data:</strong> ubah langsung di app.</li>
        <li><strong>Menghapus data:</strong> hapus isinya di app, atau hapus seluruh akun lewat Pengaturan &gt; Hapus akun atau <a href="{{ route('account-deletion') }}">halaman hapus akun</a>.</li>
        <li><strong>Menarik persetujuan notifikasi:</strong> matikan di Pengaturan &gt; Notifikasi.</li>
        <li><strong>Bertanya atau mengajukan keberatan:</strong> kirim email ke alamat di atas.</li>
    </ul>

    <h2>Keamanan</h2>
    <p>Semua koneksi memakai HTTPS. Kata sandi disimpan sebagai hash, foto disimpan di penyimpanan privat yang hanya bisa dibuka lewat tautan sementara, dan panel admin hanya bisa dibuka satu akun pengelola. Kalau terjadi kegagalan pelindungan data, kami memberi tahu kamu dan lembaga yang berwenang paling lambat 3 &times; 24 jam, sesuai UU PDP.</p>

    <h2>Usia</h2>
    <p>{{ config('app.name') }} untuk pengguna berusia 18 tahun ke atas. Kami tidak sengaja mengumpulkan data anak. Kalau ternyata ada akun milik anak, akun itu kami hapus.</p>

    <h2>Perubahan kebijakan</h2>
    <p>Kalau kebijakan ini berubah, tanggal berlaku di atas ikut berubah. Perubahan penting kami beri tahu lewat app sebelum berlaku.</p>
</x-public-page>

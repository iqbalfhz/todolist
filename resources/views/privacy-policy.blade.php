@php
    $contactLink = filled($supportEmail) ? '<a href="mailto:'.e($supportEmail).'">'.e($supportEmail).'</a>' : null;
@endphp
<x-public-page :title="'Kebijakan Privasi '.config('app.name')">
    <h1>Kebijakan Privasi {{ config('app.name') }}</h1>
    <p>Berlaku sejak 4 November 2026.</p>
    <p>Kebijakan Privasi ini menjelaskan bagaimana {{ config('app.name') }} mengumpulkan, menggunakan, menyimpan, membagikan, dan menghapus Data Pribadi Anda, serta hak yang Anda miliki atas data tersebut. Kebijakan ini disusun dengan mengacu pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi ("UU PDP") dan peraturan pelaksananya. Dengan membuat akun atau menggunakan {{ config('app.name') }}, Anda menyatakan telah membaca dan memahami Kebijakan Privasi ini.</p>

    <h2>1. Pengendali Data Pribadi</h2>
    <p>{{ config('app.name') }} ("Layanan") dikelola oleh Iqbal Fahrozi ("kami"), yang bertindak sebagai Pengendali Data Pribadi atas Data Pribadi yang diproses melalui aplikasi Android {{ config('app.name') }} dan server di {{ parse_url(config('app.url'), PHP_URL_HOST) }}.@if ($contactLink) Pertanyaan, permintaan, dan pengaduan terkait Data Pribadi dapat dikirim ke {!! $contactLink !!}. Kami menanggapi paling lambat 3 &times; 24 jam sejak permintaan diterima.@endif</p>

    <h2>2. Istilah</h2>
    <ul>
        <li><strong>Data Pribadi:</strong> data tentang orang perseorangan yang teridentifikasi atau dapat diidentifikasi, sebagaimana dimaksud dalam UU PDP.</li>
        <li><strong>Pemrosesan:</strong> setiap kegiatan terhadap Data Pribadi, termasuk memperoleh, menyimpan, memperbaiki, menampilkan, mengirim, dan menghapus.</li>
        <li><strong>Ruang Pribadi:</strong> ruang yang hanya dapat dilihat oleh pemilik akun.</li>
        <li><strong>Ruang Bersama:</strong> ruang yang dibagikan oleh tepat dua akun yang terhubung melalui kode undangan. Anggota lain dalam Ruang Bersama Anda disebut "Pasangan".</li>
        <li><strong>Konten:</strong> data yang Anda buat di Layanan, seperti tugas, catatan tabungan, perjalanan, kenangan, foto, komentar, dan reaksi.</li>
    </ul>

    <h2>3. Data Pribadi yang Kami Proses</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Kategori</th><th>Data</th><th>Sumber</th></tr>
            </thead>
            <tbody>
                <tr><td>Data akun</td><td>Nama, alamat email, kata sandi (disimpan sebagai hash), status verifikasi email, warna diri, zona waktu, mata uang pilihan, dan pengaturan notifikasi</td><td>Anda</td></tr>
                <tr><td>Data akun Google</td><td>Nama, alamat email, dan ID akun Google, hanya jika Anda memilih masuk dengan Google</td><td>Google, atas permintaan Anda</td></tr>
                <tr><td>Data profil pilihan</td><td>Foto profil dan tanggal lahir, dengan tahun yang boleh dikosongkan</td><td>Anda</td></tr>
                <tr><td>Konten</td><td>Daftar dan tugas, target dan catatan tabungan beserta nominalnya, tanggal penting, perjalanan, kenangan beserta nama tempat dan titik peta yang Anda pasang sendiri, foto kenangan beserta waktu pengambilannya, komentar, dan reaksi</td><td>Anda dan Pasangan</td></tr>
                <tr><td>Data Ruang Bersama</td><td>Keanggotaan ruang dan kode undangan, serta nama, warna, foto profil, dan tanggal lahir Pasangan</td><td>Anda dan Pasangan</td></tr>
                <tr><td>Data perangkat</td><td>Token notifikasi Firebase, jenis platform, dan token masuk untuk setiap perangkat</td><td>Perangkat Anda</td></tr>
                <tr><td>Data keamanan dan teknis</td><td>Alamat IP dan waktu permintaan; kode verifikasi, kode atur ulang kata sandi, dan kode ganti email (disimpan sebagai hash); serta alamat email baru yang menunggu konfirmasi</td><td>Perangkat Anda dan server</td></tr>
            </tbody>
        </table>
    </div>
    <p>Foto profil dan foto kenangan disimpan ulang oleh server tanpa metadata EXIF, termasuk lokasi yang mungkin tersimpan di foto. Catatan tabungan hanyalah catatan yang Anda ketik sendiri; Layanan tidak terhubung dengan rekening bank atau alat pembayaran apa pun. Jika Anda mengimpor file ekspor, isinya disimpan sebagai Konten baru di ruang yang Anda pilih.</p>

    <h2>4. Data yang Tidak Kami Kumpulkan</h2>
    <p>Layanan tidak meminta izin lokasi perangkat dan tidak menggunakan lokasi GPS, tidak membaca kontak, tidak menampilkan iklan, dan tidak menggunakan alat analitik atau pelacak pihak ketiga. Kami tidak menjual Data Pribadi, tidak membuat profil tentang Anda, dan tidak mengambil keputusan yang semata-mata didasarkan pada pemrosesan otomatis yang menimbulkan akibat hukum bagi Anda.</p>

    <h2>5. Tujuan dan Dasar Pemrosesan</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Tujuan</th><th>Dasar pemrosesan (Pasal 20 UU PDP)</th></tr>
            </thead>
            <tbody>
                <tr><td>Membuat dan mengelola akun, termasuk masuk dengan email atau Google, verifikasi email, atur ulang kata sandi, serta ganti email dan kata sandi</td><td>Pemenuhan kewajiban perjanjian, yaitu <a href="{{ route('terms') }}">Syarat Layanan</a></td></tr>
                <tr><td>Menyimpan Konten dan menyinkronkannya ke semua perangkat Anda serta ke Pasangan di Ruang Bersama</td><td>Pemenuhan kewajiban perjanjian</td></tr>
                <tr><td>Mengirim notifikasi pengingat dan aktivitas Pasangan</td><td>Persetujuan, melalui izin notifikasi di perangkat dan pengaturan notifikasi di Layanan</td></tr>
                <tr><td>Mengirim email berisi kode dan pemberitahuan keamanan akun</td><td>Pemenuhan kewajiban perjanjian dan kepentingan yang sah untuk melindungi akun Anda</td></tr>
                <tr><td>Menyediakan ekspor dan impor data, pencarian tempat, dan perkiraan kurs</td><td>Pemenuhan kewajiban perjanjian</td></tr>
                <tr><td>Menjaga keamanan Layanan, misalnya membatasi percobaan masuk dan mencegah penyalahgunaan</td><td>Kepentingan yang sah</td></tr>
                <tr><td>Menanggapi permintaan bantuan dan permintaan atas hak Anda sebagai Subjek Data Pribadi</td><td>Pemenuhan kewajiban hukum dan kewajiban perjanjian</td></tr>
                <tr><td>Memenuhi kewajiban menurut peraturan perundang-undangan atau perintah yang sah dari pihak berwenang</td><td>Pemenuhan kewajiban hukum</td></tr>
            </tbody>
        </table>
    </div>
    <p>Data profil pilihan, foto, dan titik peta hanya diproses jika Anda memberikannya, dan dapat Anda hapus kapan saja.</p>

    <h2>6. Pihak yang Dapat Melihat Data Anda</h2>
    <ul>
        <li><strong>Anda:</strong> seluruh isi Ruang Pribadi dan Ruang Bersama Anda.</li>
        <li><strong>Pasangan:</strong> seluruh isi Ruang Bersama, serta nama, warna, foto profil, dan tanggal lahir Anda. Isi Ruang Pribadi dan alamat email Anda tidak pernah ditampilkan kepada Pasangan.</li>
        <li><strong>Pengelola:</strong> hanya data yang dibutuhkan untuk bantuan teknis, yaitu nama, alamat email, keanggotaan ruang, kode undangan, dan token perangkat. Panel admin tidak menampilkan isi tugas, tabungan, maupun kenangan Anda.</li>
        <li><strong>Penyedia layanan:</strong> sebagaimana dijelaskan pada bagian 7, hanya sebatas yang dibutuhkan untuk tugas masing-masing.</li>
        <li><strong>Pihak berwenang:</strong> jika diwajibkan oleh peraturan perundang-undangan atau perintah yang sah, dan hanya sebatas yang diwajibkan.</li>
    </ul>

    <h2>7. Penyedia Layanan dan Transfer Data ke Luar Indonesia</h2>
    <p>Kami menggunakan penyedia layanan berikut sebagai Prosesor Data Pribadi, dan hanya memberikan data yang dibutuhkan untuk tugas masing-masing.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Penyedia</th><th>Peran</th><th>Data yang diterima</th></tr>
            </thead>
            <tbody>
                <tr><td>Cloudflare, Inc.</td><td>Jaringan dan keamanan koneksi, penyimpanan foto, dan penyimpanan cadangan database</td><td>Data yang dikirim antara perangkat dan server, foto, dan cadangan database</td></tr>
                <tr><td>Google LLC (Firebase Cloud Messaging dan Google Sign-In)</td><td>Mengirim notifikasi ke perangkat, dan memverifikasi akun saat Anda masuk dengan Google</td><td>Token notifikasi dan isi notifikasi, seperti judul tugas atau nominal setoran; nama, alamat email, dan ID akun Google</td></tr>
                <tr><td>Resend</td><td>Mengirim email berisi kode dan pemberitahuan keamanan akun</td><td>Alamat email, nama, dan isi email</td></tr>
                <tr><td>OpenStreetMap Foundation</td><td>Menyediakan peta dan pencarian tempat</td><td>Alamat IP perangkat dan bagian peta yang dibuka, karena peta dimuat langsung oleh perangkat; kata kunci pencarian tempat, yang dikirim melalui server kami tanpa data akun</td></tr>
            </tbody>
        </table>
    </div>
    <p>Aplikasi dan database berjalan di server yang dikelola sendiri oleh Iqbal Fahrozi, tanpa penyedia server pihak ketiga.</p>
    <p>Penyedia tersebut dapat memproses dan menyimpan data di luar wilayah Indonesia. Transfer Data Pribadi ke luar Indonesia dilakukan sesuai Pasal 56 UU PDP, dengan memilih penyedia yang menerapkan pelindungan data yang memadai dan mengikat melalui ketentuan layanan dan perjanjian pemrosesan data mereka.</p>

    <h2>8. Masa Simpan dan Penghapusan</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Data</th><th>Masa simpan</th></tr>
            </thead>
            <tbody>
                <tr><td>Data akun dan Konten</td><td>Selama akun Anda ada</td></tr>
                <tr><td>Konten yang Anda hapus</td><td>Ditandai terhapus dan disimpan 30 hari sebagai catatan sinkronisasi, agar perangkat lain ikut menghapusnya, lalu dihapus permanen. Foto yang dihapus langsung dibuang dari penyimpanan</td></tr>
                <tr><td>Ruang Bersama yang ditutup karena salah satu anggota keluar</td><td>30 hari, agar dapat dipulihkan utuh jika kedua anggota bergabung kembali, lalu dihapus permanen</td></tr>
                <tr><td>Kode verifikasi, kode atur ulang kata sandi, dan kode ganti email</td><td>Berlaku 60 menit; dihapus setelah dipakai atau paling lama satu hari setelah kedaluwarsa, bersama alamat email baru yang menunggu konfirmasi</td></tr>
                <tr><td>Kode undangan</td><td>Berlaku 48 jam; dihapus 30 hari setelah kedaluwarsa</td></tr>
                <tr><td>Catatan pengingat yang sudah terkirim</td><td>30 hari</td></tr>
                <tr><td>Notifikasi yang gagal terkirim, beserta isinya</td><td>Paling lama 30 hari</td></tr>
                <tr><td>Catatan teknis seperti alamat IP</td><td>Hanya selama dibutuhkan untuk keamanan dan penanganan gangguan</td></tr>
                <tr><td>Seluruh data saat akun dihapus</td><td>Langsung dihapus permanen; salinan dalam cadangan database terhapus paling lama 30 hari kemudian</td></tr>
            </tbody>
        </table>
    </div>
    <p>Jika Anda menghapus akun, isi Ruang Bersama ikut dihapus permanen, termasuk bagi Pasangan, karena Ruang Bersama tidak dapat dilanjutkan tanpa kedua anggotanya. Kami menyarankan Anda mengekspor data lebih dulu.</p>

    <h2>9. Keamanan Data</h2>
    <p>Kami menerapkan langkah teknis dan organisasi yang wajar untuk melindungi Data Pribadi, antara lain:</p>
    <ul>
        <li>seluruh koneksi dienkripsi dengan HTTPS;</li>
        <li>kata sandi, token masuk, dan kode verifikasi disimpan dalam bentuk hash, bukan teks asli;</li>
        <li>foto disimpan di penyimpanan privat yang hanya dapat dibuka melalui tautan sementara yang berlaku paling lama 7 hari;</li>
        <li>akses ke data dibatasi berdasarkan keanggotaan ruang, dan panel admin hanya dapat dibuka oleh satu akun pengelola;</li>
        <li>jumlah percobaan masuk dan permintaan dibatasi untuk mencegah penyalahgunaan.</li>
    </ul>
    <p>Tidak ada sistem yang sepenuhnya bebas risiko. Jika terjadi kegagalan Pelindungan Data Pribadi, kami memberitahukannya secara tertulis kepada Anda dan kepada lembaga yang berwenang paling lambat 3 &times; 24 jam sesuai Pasal 46 UU PDP, beserta data yang terungkap, waktu dan cara terjadinya, serta upaya penanganan dan pemulihannya.</p>

    <h2>10. Hak Anda sebagai Subjek Data Pribadi</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Hak</th><th>Cara menggunakan</th></tr>
            </thead>
            <tbody>
                <tr><td>Mendapatkan informasi tentang pemrosesan Data Pribadi (Pasal 5)</td><td>Kebijakan Privasi ini{!! $contactLink ? ', atau email ke '.$contactLink : '' !!}</td></tr>
                <tr><td>Melengkapi, memperbarui, dan memperbaiki data (Pasal 6)</td><td>Langsung di aplikasi, termasuk nama, profil, email, dan kata sandi</td></tr>
                <tr><td>Mengakses dan memperoleh salinan data (Pasal 7)</td><td>Pengaturan &gt; Ekspor data, sebagai file JSON atau ZIP berisi file itu dan semua foto Anda</td></tr>
                <tr><td>Mengakhiri pemrosesan dan menghapus data (Pasal 8)</td><td>Menghapus isi di aplikasi, atau menghapus akun melalui Pengaturan &gt; Hapus akun atau halaman <a href="{{ route('account-deletion') }}">/hapus-akun</a></td></tr>
                <tr><td>Menarik kembali persetujuan (Pasal 9)</td><td>Mematikan notifikasi di Pengaturan &gt; Notifikasi atau di pengaturan perangkat; untuk pemrosesan lainnya, dengan menghapus akun</td></tr>
                <tr><td>Mengajukan keberatan atas keputusan berdasarkan pemrosesan otomatis (Pasal 10)</td><td>Layanan tidak membuat keputusan seperti itu</td></tr>
                <tr><td>Menunda atau membatasi pemrosesan (Pasal 11)</td><td>{!! $contactLink ? 'Email ke '.$contactLink : 'Hubungi pengelola Layanan' !!}</td></tr>
                <tr><td>Menggugat dan menerima ganti rugi (Pasal 12)</td><td>Sesuai peraturan perundang-undangan</td></tr>
                <tr><td>Memperoleh dan menggunakan data dalam format yang dapat dibaca sistem elektronik (Pasal 13)</td><td>Ekspor data dalam format JSON, yang juga dapat diimpor kembali ke akun {{ config('app.name') }}</td></tr>
            </tbody>
        </table>
    </div>
    @if ($contactLink)
        <p>Permintaan melalui email kami tanggapi paling lambat 3 &times; 24 jam. Untuk melindungi akun Anda, kami dapat meminta Anda mengirim permintaan dari alamat email akun tersebut.</p>
    @endif

    <h2>11. Pengguna di Bawah Umur</h2>
    <p>Layanan ditujukan bagi pengguna berusia 18 tahun ke atas. Kami tidak dengan sengaja memproses Data Pribadi anak. Jika kami mengetahui bahwa suatu akun dimiliki oleh anak, akun tersebut beserta datanya akan kami hapus.</p>

    <h2>12. Notifikasi dan Email</h2>
    <p>Notifikasi dikirim sesuai izin notifikasi di perangkat dan pengaturan di Layanan, dan dapat dimatikan kapan saja. Isi notifikasi tabungan disembunyikan di layar kunci. Email dari kami hanya berisi kode dan pemberitahuan keamanan akun; kami tidak mengirim email promosi.</p>

    <h2>13. Informasi dari Google</h2>
    <p>Jika Anda memilih masuk dengan Google, kami hanya menerima nama, alamat email, dan ID akun Google Anda, dan hanya menggunakannya untuk membuat atau menghubungkan akun serta memastikan bahwa Anda adalah pemilik akun. Penggunaan dan pemindahan informasi yang diterima dari API Google mematuhi Kebijakan Data Pengguna Layanan API Google, termasuk persyaratan Penggunaan Terbatas (Limited Use).</p>

    <h2>14. Perubahan Kebijakan Privasi</h2>
    <p>Kami dapat mengubah Kebijakan Privasi ini untuk menyesuaikan dengan perubahan Layanan atau peraturan perundang-undangan, dan tanggal berlaku di bagian atas akan diperbarui. Perubahan yang material akan kami beritahukan melalui aplikasi atau email sebelum berlaku.</p>

    <h2>15. Kontak dan Pengaduan</h2>
    <p>Untuk pertanyaan, permintaan atas hak Anda, atau pengaduan terkait Data Pribadi, hubungi Iqbal Fahrozi{!! $contactLink ? ' di '.$contactLink : ', pengelola '.e(config('app.name')) !!}. Jika Anda tidak puas dengan tanggapan kami, Anda dapat menyampaikan pengaduan kepada lembaga yang berwenang di bidang pelindungan data pribadi sesuai peraturan perundang-undangan.</p>
</x-public-page>

@php
    $contactLink = filled($supportEmail) ? '<a href="mailto:'.e($supportEmail).'">'.e($supportEmail).'</a>' : null;
@endphp
<x-public-page :title="'Syarat Layanan '.config('app.name')">
    <h1>Syarat Layanan {{ config('app.name') }}</h1>
    <p>Berlaku sejak 4 November 2026.</p>
    <p>Syarat Layanan ini ("Syarat") merupakan perjanjian antara Anda dan Iqbal Fahrozi ("kami"), pengelola {{ config('app.name') }} ("Layanan"). <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a> merupakan bagian yang tidak terpisahkan dari Syarat ini. Dengan membuat akun atau menggunakan Layanan, Anda menyatakan telah membaca, memahami, dan menyetujui Syarat ini. Jika Anda tidak menyetujuinya, mohon tidak menggunakan Layanan.</p>

    <h2>1. Istilah</h2>
    <ul>
        <li><strong>Akun:</strong> akun {{ config('app.name') }} yang Anda buat dengan alamat email atau akun Google.</li>
        <li><strong>Ruang Pribadi:</strong> ruang yang hanya dapat dilihat oleh pemilik Akun.</li>
        <li><strong>Ruang Bersama:</strong> ruang yang dibagikan oleh tepat dua Akun yang terhubung melalui kode undangan. Anggota lain dalam Ruang Bersama Anda disebut "Pasangan".</li>
        <li><strong>Konten:</strong> data yang Anda buat di Layanan, seperti tugas, catatan tabungan, perjalanan, kenangan, foto, komentar, dan reaksi.</li>
    </ul>

    <h2>2. Ketentuan Pengguna</h2>
    <p>Dengan menggunakan Layanan, Anda menyatakan bahwa:</p>
    <ol>
        <li>Anda berusia 18 tahun ke atas dan cakap melakukan perbuatan hukum menurut peraturan perundang-undangan;</li>
        <li>data yang Anda berikan saat mendaftar, termasuk nama dan alamat email, benar dan milik Anda sendiri; dan</li>
        <li>satu Akun hanya digunakan oleh satu orang.</li>
    </ol>

    <h2>3. Akun dan Keamanan</h2>
    <ol>
        <li>Anda wajib menjaga kerahasiaan kata sandi dan keamanan perangkat yang Anda gunakan untuk masuk. Aktivitas melalui Akun Anda menjadi tanggung jawab Anda, kecuali terjadi karena kelalaian kami.</li>
        <li>Jika Anda mengetahui atau menduga Akun Anda digunakan tanpa izin, segera ganti kata sandi{!! $contactLink ? ' dan beri tahu kami di '.$contactLink : '' !!}.</li>
        <li>Jika Anda masuk dengan akun Google, penggunaan akun Google Anda juga tunduk pada ketentuan Google.</li>
    </ol>

    <h2>4. Ruang Bersama</h2>
    <ol>
        <li>Ruang Bersama dibuat saat salah satu pengguna memasukkan kode undangan dari pengguna lainnya, dan berisi tepat dua Akun.</li>
        <li>Kedua anggota dapat melihat, mengubah, dan menghapus seluruh Konten di Ruang Bersama, kecuali komentar dan reaksi milik anggota lain.</li>
        <li>Jika salah satu anggota keluar, Ruang Bersama ditutup bagi kedua anggota. Kontennya disimpan selama 30 hari dan dipulihkan utuh jika kedua anggota bergabung kembali; setelah itu Konten dihapus permanen.</li>
        <li>Jika salah satu anggota menghapus Akunnya, Konten Ruang Bersama langsung dihapus permanen, termasuk bagi anggota lainnya.</li>
        <li>Dengan menambahkan Konten ke Ruang Bersama, Anda memahami bahwa Konten tersebut dapat dilihat, diubah, dan dihapus oleh Pasangan Anda.</li>
    </ol>

    <h2>5. Konten Anda</h2>
    <ol>
        <li>Konten yang Anda buat tetap menjadi milik Anda.</li>
        <li>Anda memberikan kepada kami izin yang terbatas, tidak eksklusif, dan tanpa royalti untuk menyimpan, memproses, mencadangkan, dan menampilkan Konten kepada Anda dan anggota ruang Anda, semata-mata untuk menjalankan Layanan. Izin tersebut berakhir ketika Konten atau Akun Anda dihapus, sesuai masa simpan dalam Kebijakan Privasi.</li>
        <li>Anda bertanggung jawab atas Konten yang Anda unggah, termasuk memastikan bahwa Anda berhak mengunggahnya. Jika Konten memuat Data Pribadi orang lain, seperti nama atau foto, pastikan Anda memiliki dasar yang sah untuk itu.</li>
        <li>Anda dapat menyalin Konten melalui fitur ekspor data dan memasukkannya kembali melalui fitur impor data.</li>
    </ol>

    <h2>6. Larangan</h2>
    <p>Anda dilarang:</p>
    <ol>
        <li>menggunakan Layanan untuk melanggar hukum, atau menyimpan dan membagikan Konten yang melanggar hukum, kesusilaan, atau hak pihak lain;</li>
        <li>mengakses atau mencoba mengakses Akun, ruang, atau data milik orang lain tanpa izin;</li>
        <li>mencoba membobol, mengganggu, membebani, atau merekayasa balik Layanan, server, maupun sistem keamanannya;</li>
        <li>mengakses Layanan secara otomatis atau massal, termasuk menggunakan pencarian tempat untuk mengumpulkan data;</li>
        <li>menyamar sebagai orang lain atau membuat Akun dengan identitas orang lain; dan</li>
        <li>menggunakan Layanan untuk mengirim spam atau perangkat lunak berbahaya.</li>
    </ol>

    <h2>7. Layanan</h2>
    <ol>
        <li>Layanan saat ini disediakan tanpa biaya. Jika di kemudian hari ada fitur berbayar, ketentuannya akan diberitahukan lebih dulu dan tidak berlaku tanpa persetujuan Anda.</li>
        <li>Kami berupaya menjaga Layanan tetap tersedia dan data Anda tetap aman, tetapi Layanan dapat terganggu, misalnya karena pemeliharaan, gangguan jaringan, atau gangguan pada penyedia layanan kami.</li>
        <li>Kami dapat menambah, mengubah, atau menghentikan fitur. Jika Layanan akan dihentikan seluruhnya, kami memberitahukannya paling lambat 30 hari sebelumnya agar Anda dapat mengekspor data.</li>
        <li>Fitur tabungan hanya berfungsi sebagai catatan. Layanan tidak menyimpan, memindahkan, atau mengelola uang, dan tidak terhubung dengan bank atau alat pembayaran.</li>
        <li>Perkiraan kurs menggunakan kurs referensi harian Bank Sentral Eropa (ECB) dan hanya bersifat gambaran, bukan saran keuangan. Kurs di bank atau tempat penukaran uang dapat berbeda.</li>
        <li>Notifikasi dikirim melalui layanan pihak ketiga dan dapat terlambat atau tidak sampai. Jangan mengandalkan Layanan sebagai satu-satunya pengingat untuk hal yang penting atau mendesak.</li>
        <li>Peta dan pencarian tempat menggunakan data OpenStreetMap &copy; kontributor OpenStreetMap.</li>
    </ol>

    <h2>8. Penangguhan dan Penghentian</h2>
    <ol>
        <li>Anda dapat berhenti menggunakan Layanan kapan saja dan menghapus Akun melalui Pengaturan &gt; Hapus akun.</li>
        <li>Kami dapat menangguhkan atau menghapus Akun yang melanggar Syarat ini atau peraturan perundang-undangan. Kecuali untuk mencegah kerugian yang mendesak atau karena diwajibkan hukum, kami memberitahukan alasannya lebih dulu dan memberi Anda kesempatan untuk menanggapi dan mengekspor data.</li>
        <li>Setelah Akun dihapus, data diperlakukan sesuai masa simpan dalam Kebijakan Privasi.</li>
    </ol>

    <h2>9. Batasan Tanggung Jawab</h2>
    <ol>
        <li>Sepanjang diizinkan oleh peraturan perundang-undangan, kami tidak bertanggung jawab atas kerugian tidak langsung, kehilangan keuntungan, atau kerugian yang timbul dari keadaan di luar kendali kami yang wajar.</li>
        <li>Kami tidak bertanggung jawab atas Konten yang dibuat oleh pengguna, termasuk Konten yang ditambahkan Pasangan ke Ruang Bersama.</li>
        <li>Ketentuan dalam bagian ini tidak mengurangi hak Anda sebagai konsumen menurut peraturan perundang-undangan, dan tidak membatasi tanggung jawab kami atas kerugian yang timbul karena kesengajaan atau kelalaian kami.</li>
    </ol>

    <h2>10. Keadaan Kahar</h2>
    <p>Kami tidak dianggap lalai atas keterlambatan atau kegagalan menjalankan Layanan yang disebabkan oleh keadaan di luar kendali yang wajar, seperti bencana alam, gangguan listrik atau jaringan berskala luas, gangguan pada penyedia layanan, kebijakan pemerintah, atau serangan siber, sepanjang kami telah berupaya mengurangi dampaknya.</p>

    <h2>11. Perubahan Syarat</h2>
    <ol>
        <li>Kami dapat mengubah Syarat ini, dan tanggal berlaku di bagian atas akan diperbarui.</li>
        <li>Perubahan yang material kami beritahukan melalui aplikasi atau email paling lambat 14 hari sebelum berlaku, kecuali perubahan yang diwajibkan hukum.</li>
        <li>Jika Anda tidak menyetujui perubahan, Anda dapat berhenti menggunakan Layanan dan menghapus Akun sebelum perubahan berlaku. Jika Anda tetap menggunakan Layanan setelah tanggal berlaku, Anda dianggap menyetujui Syarat yang baru.</li>
    </ol>

    <h2>12. Hukum yang Berlaku dan Penyelesaian Sengketa</h2>
    <ol>
        <li>Syarat ini diatur oleh dan ditafsirkan berdasarkan hukum Republik Indonesia.</li>
        <li>Setiap perselisihan diselesaikan lebih dulu secara musyawarah dalam waktu 30 hari sejak salah satu pihak memberitahukannya secara tertulis.</li>
        <li>Jika musyawarah tidak berhasil, perselisihan diselesaikan melalui pengadilan negeri yang berwenang atau melalui lembaga penyelesaian sengketa konsumen sesuai peraturan perundang-undangan.</li>
    </ol>

    <h2>13. Ketentuan Lain</h2>
    <ol>
        <li>Syarat ini dibuat dalam Bahasa Indonesia. Jika ada terjemahan, versi Bahasa Indonesia yang berlaku.</li>
        <li>Jika salah satu ketentuan dinyatakan tidak sah atau tidak dapat dilaksanakan, ketentuan lainnya tetap berlaku.</li>
        <li>Tidak digunakannya suatu hak oleh kami tidak berarti pelepasan hak tersebut.</li>
        <li>Anda tidak dapat mengalihkan Akun atau hak Anda berdasarkan Syarat ini kepada pihak lain tanpa persetujuan kami.</li>
    </ol>

    <h2>14. Kontak</h2>
    <p>Pertanyaan tentang Syarat ini dapat {!! $contactLink ? 'dikirim kepada Iqbal Fahrozi di '.$contactLink : 'disampaikan kepada Iqbal Fahrozi, pengelola '.e(config('app.name')) !!}.</p>
</x-public-page>

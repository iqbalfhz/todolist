<x-public-page :title="'Syarat layanan '.config('app.name')">
    <h1>Syarat layanan {{ config('app.name') }}</h1>
    <p>Berlaku sejak 1 November 2026.</p>
    <p>Dengan membuat akun atau memakai {{ config('app.name') }}, kamu menyetujui syarat ini dan <a href="{{ route('privacy-policy') }}">Kebijakan privasi</a>. {{ config('app.name') }} dikelola oleh Iqbal Fahrozi.</p>

    <h2>Siapa yang boleh memakai</h2>
    <ul>
        <li>Kamu berusia 18 tahun ke atas.</li>
        <li>Satu akun untuk satu orang, dengan nama dan email yang benar.</li>
    </ul>

    <h2>Akunmu</h2>
    <ul>
        <li>Jaga kata sandimu dan HP tempat kamu masuk. Kamu bertanggung jawab atas yang dilakukan lewat akunmu.</li>
        @if (filled($supportEmail))
            <li>Kalau akunmu dipakai orang lain tanpa izin, beri tahu kami di <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</li>
        @endif
    </ul>

    <h2>Ruang Bersama</h2>
    <ul>
        <li>Ruang Bersama berisi tepat dua orang, dibuat saat salah satu memakai kode undangan dari yang lain.</li>
        <li>Kalian berdua bisa melihat, mengubah, dan menghapus semua isinya, kecuali komentar dan reaksi milik orang lain.</li>
        <li>Kalau salah satu keluar, Ruang Bersama ditutup untuk kalian berdua. Isinya disimpan 30 hari dan kembali utuh kalau kalian bergabung lagi, lalu dihapus permanen.</li>
        <li>Kalau salah satu menghapus akunnya, isi Ruang Bersama langsung dihapus permanen, juga untuk pasangannya.</li>
    </ul>

    <h2>Isi milikmu</h2>
    <p>Tugas, catatan tabungan, kenangan, foto, dan isi lain yang kamu buat tetap milikmu. Kamu memberi kami izin terbatas untuk menyimpan, memproses, dan menampilkannya kepadamu dan anggota ruangmu, hanya untuk menjalankan {{ config('app.name') }}. Izin itu berakhir saat isinya atau akunmu dihapus, sesuai masa simpan di Kebijakan privasi.</p>

    <h2>Yang tidak boleh</h2>
    <ul>
        <li>Memakai {{ config('app.name') }} untuk melanggar hukum, atau menyimpan isi yang melanggar hukum atau hak orang lain.</li>
        <li>Mencoba membobol, mengganggu, atau membebani server, termasuk memakai pencarian tempat secara otomatis atau massal.</li>
        <li>Membuat atau memakai akun orang lain tanpa izinnya.</li>
    </ul>

    <h2>Layanan</h2>
    <ul>
        <li>{{ config('app.name') }} gratis dan disediakan sebagaimana adanya. Kami berusaha menjaga datamu tetap aman dan layanan tetap berjalan, tetapi tidak menjamin layanan selalu tersedia atau bebas gangguan.</li>
        <li>Fitur bisa berubah, bertambah, atau dihentikan. Simpan salinan datamu lewat Ekspor data kalau kamu membutuhkannya.</li>
        <li>Tabungan di {{ config('app.name') }} hanya catatan. Aplikasi ini tidak menyimpan, memindahkan, atau mengelola uang sungguhan.</li>
    </ul>

    <h2>Penghentian</h2>
    <p>Kamu bisa berhenti kapan saja dengan <a href="{{ route('account-deletion') }}">menghapus akunmu</a>. Kami bisa menangguhkan atau menghapus akun yang melanggar syarat ini, dan sebisa mungkin memberi tahu pemiliknya lebih dulu.</p>

    <h2>Batas tanggung jawab</h2>
    <p>Sejauh diizinkan hukum, kami tidak bertanggung jawab atas kerugian tidak langsung karena memakai atau tidak bisa memakai {{ config('app.name') }}, termasuk data yang hilang karena gangguan di luar kendali kami.</p>

    <h2>Hukum yang berlaku</h2>
    <p>Syarat ini diatur oleh hukum Republik Indonesia.</p>

    <h2>Perubahan syarat</h2>
    <p>Kalau syarat ini berubah, tanggal berlaku di atas ikut berubah. Perubahan penting kami beri tahu lewat app sebelum berlaku. Kalau kamu tetap memakai {{ config('app.name') }} sesudahnya, kamu dianggap menyetujui syarat yang baru.</p>
</x-public-page>

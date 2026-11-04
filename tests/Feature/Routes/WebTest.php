<?php

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Get the page's own content, between the opening body tag and the footer.
 *
 * Livewire adds its assets to the head and the end of the body of every page
 * once an earlier test in the same process has rendered a component.
 */
function publicPageContent(TestResponse $response): string
{
    return Str::between((string) $response->getContent(), '<body>', '<footer>');
}

it('shows how to delete an account in the app and by email to the support address', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);

    $response = $this->get('/hapus-akun');

    $response->assertOk()
        ->assertSee('Hapus akun Denly Days')
        ->assertSee('Pengaturan &gt; Ekspor data', false)
        ->assertSee('Pilih Hapus akun.')
        ->assertSee('mailto:bantuan@contoh.test?subject=Hapus%20akun', false)
        ->assertSee('paling lambat 3 &times; 24 jam', false)
        ->assertSee('isinya juga hilang dari akun pasanganmu')
        ->assertSee('paling lama 30 hari setelah akun dihapus');
});

it('leaves the request by email out while no support address is set', function () {
    config(['admin.support_email' => null]);

    $response = $this->get('/hapus-akun');

    $response->assertOk()
        ->assertSee('Pilih Hapus akun.')
        ->assertDontSee('Tanpa app')
        ->assertDontSee('mailto:', false);
});

it('shows the privacy policy approved by the owner, with its controller, bases, providers, retention, and rights', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);

    $response = $this->get('/privasi');

    $response->assertOk()
        ->assertSee('<title>Kebijakan Privasi Denly Days</title>', false)
        ->assertSee('Berlaku sejak 4 November 2026.')
        ->assertSee('dikelola oleh Iqbal Fahrozi ("kami"), yang bertindak sebagai Pengendali Data Pribadi', false)
        ->assertSee('server di '.parse_url(config('app.url'), PHP_URL_HOST).'. Pertanyaan, permintaan, dan pengaduan terkait Data Pribadi dapat dikirim ke <a href="mailto:bantuan@contoh.test">bantuan@contoh.test</a>.', false)
        ->assertSee('Layanan tidak meminta izin lokasi perangkat dan tidak menggunakan lokasi GPS')
        ->assertSee('Dasar pemrosesan (Pasal 20 UU PDP)')
        ->assertSee('<td>Cloudflare, Inc.</td>', false)
        ->assertSee('<td>Google LLC (Firebase Cloud Messaging dan Google Sign-In)</td>', false)
        ->assertSee('<td>Resend</td>', false)
        ->assertSee('<td>OpenStreetMap Foundation</td>', false)
        ->assertSee('Aplikasi dan database berjalan di server yang dikelola sendiri oleh Iqbal Fahrozi, tanpa penyedia server pihak ketiga.')
        ->assertSee('Transfer Data Pribadi ke luar Indonesia dilakukan sesuai Pasal 56 UU PDP')
        ->assertSee('Berlaku 60 menit; dihapus setelah dipakai atau paling lama satu hari setelah kedaluwarsa, bersama alamat email baru yang menunggu konfirmasi')
        ->assertSee('paling lambat 3 &times; 24 jam sesuai Pasal 46 UU PDP', false)
        ->assertSee('Pengaturan &gt; Ekspor data, sebagai file JSON atau ZIP berisi file itu dan semua foto Anda', false)
        ->assertSee('<td>Email ke <a href="mailto:bantuan@contoh.test">bantuan@contoh.test</a></td>', false)
        ->assertSee('termasuk persyaratan Penggunaan Terbatas (Limited Use)')
        ->assertSee('Layanan ditujukan bagi pengguna berusia 18 tahun ke atas.');

    expect(publicPageContent($response))->not->toContain('[');
});

it('shows the terms of service approved by the owner, with the rules of the shared space and the disputes', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);

    $response = $this->get('/syarat');

    $response->assertOk()
        ->assertSee('<title>Syarat Layanan Denly Days</title>', false)
        ->assertSee('Berlaku sejak 4 November 2026.')
        ->assertSee('merupakan perjanjian antara Anda dan Iqbal Fahrozi ("kami"), pengelola Denly Days', false)
        ->assertSee('Anda berusia 18 tahun ke atas dan cakap melakukan perbuatan hukum menurut peraturan perundang-undangan;')
        ->assertSee('segera ganti kata sandi dan beri tahu kami di <a href="mailto:bantuan@contoh.test">bantuan@contoh.test</a>.', false)
        ->assertSee('Ruang Bersama dibuat saat salah satu pengguna memasukkan kode undangan dari pengguna lainnya, dan berisi tepat dua Akun.')
        ->assertSee('Fitur tabungan hanya berfungsi sebagai catatan.')
        ->assertSee('Perkiraan kurs menggunakan kurs referensi harian Bank Sentral Eropa (ECB) dan hanya bersifat gambaran, bukan saran keuangan.')
        ->assertSee('kami memberitahukannya paling lambat 30 hari sebelumnya agar Anda dapat mengekspor data.')
        ->assertSee('tidak mengurangi hak Anda sebagai konsumen menurut peraturan perundang-undangan')
        ->assertSee('paling lambat 14 hari sebelum berlaku')
        ->assertSee('Syarat ini diatur oleh dan ditafsirkan berdasarkan hukum Republik Indonesia.')
        ->assertSee('melalui pengadilan negeri yang berwenang atau melalui lembaga penyelesaian sengketa konsumen')
        ->assertSee('Pertanyaan tentang Syarat ini dapat dikirim kepada Iqbal Fahrozi di <a href="mailto:bantuan@contoh.test">bantuan@contoh.test</a>.', false);

    expect(publicPageContent($response))->not->toContain('[');
});

it('names the manager in place of the support address while none is set', function (string $path, array $sentences) {
    config(['admin.support_email' => null]);

    $response = $this->get($path);

    $response->assertOk()->assertSeeInOrder($sentences);
})->with([
    'the privacy policy' => ['/privasi', [
        'Kebijakan Privasi ini</td>',
        'Hubungi pengelola Layanan',
        'hubungi Iqbal Fahrozi, pengelola Denly Days. Jika Anda tidak puas',
    ]],
    'the terms of service' => ['/syarat', [
        'segera ganti kata sandi.</li>',
        'Pertanyaan tentang Syarat ini dapat disampaikan kepada Iqbal Fahrozi, pengelola Denly Days.',
    ]],
]);

it('links every public page to the others', function (string $path) {
    $response = $this->get($path);

    $response->assertOk()
        ->assertSee('href="'.route('privacy-policy').'"', false)
        ->assertSee('href="'.route('terms').'"', false)
        ->assertSee('href="'.route('account-deletion').'"', false);
})->with(['/privasi', '/syarat', '/hapus-akun']);

it('leaves the support address out of the policy pages while none is set', function (string $path) {
    config(['admin.support_email' => null]);

    $response = $this->get($path);

    $response->assertOk()
        ->assertDontSee('mailto:', false)
        ->assertDontSee('dikirim ke')
        ->assertDontSee('beri tahu kami di')
        ->assertDontSee('Permintaan melalui email');
})->with(['/privasi', '/syarat']);

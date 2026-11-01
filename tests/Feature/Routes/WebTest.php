<?php

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

it('shows the privacy policy with its controller, providers, retention, and rights', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);

    $response = $this->get('/privasi');

    $response->assertOk()
        ->assertSee('Kebijakan privasi Denly Days')
        ->assertSee('Berlaku sejak 1 November 2026.')
        ->assertSee('dikelola oleh Iqbal Fahrozi')
        ->assertSee('mailto:bantuan@contoh.test', false)
        ->assertSee('Kami tidak memakai lokasi GPS')
        ->assertSee('Cloudflare')
        ->assertSee('Firebase Cloud Messaging')
        ->assertSee('OpenStreetMap')
        ->assertSee('tetap tersimpan sebagai catatan sinkron sampai akunmu dihapus')
        ->assertSee('Pengaturan &gt; Ekspor data', false)
        ->assertSee('untuk pengguna berusia 18 tahun ke atas');
});

it('shows the terms of service with the rules of the shared space', function () {
    $response = $this->get('/syarat');

    $response->assertOk()
        ->assertSee('Syarat layanan Denly Days')
        ->assertSee('Berlaku sejak 1 November 2026.')
        ->assertSee('Kamu berusia 18 tahun ke atas.')
        ->assertSee('Ruang Bersama berisi tepat dua orang')
        ->assertSee('Tabungan di Denly Days hanya catatan.')
        ->assertSee('hukum Republik Indonesia');
});

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

    $response->assertOk()->assertDontSee('mailto:', false);
})->with(['/privasi', '/syarat']);

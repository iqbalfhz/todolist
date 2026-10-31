<?php

it('shows how to delete an account in the app and by email to the support address', function () {
    config(['admin.support_email' => 'bantuan@contoh.test']);

    $response = $this->get('/hapus-akun');

    $response->assertOk()
        ->assertSee('Hapus akun Denly Days')
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

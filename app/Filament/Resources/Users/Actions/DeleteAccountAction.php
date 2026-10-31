<?php

namespace App\Filament\Resources\Users\Actions;

use App\Actions\DeleteAccount;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Deletes an account for someone who asked by email, as the page at
 * /hapus-akun offers, in the same way as deleting it in the app.
 */
class DeleteAccountAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'deleteAccount';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Hapus akun');

        $this->icon(Heroicon::OutlinedTrash);

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalHeading('Hapus akun ini?');

        $this->modalDescription('Semua data akun ini dihapus permanen, termasuk Ruang Pribadi dan isi Ruang Bersama, yang ikut hilang untuk pasangannya. Pakai hanya untuk permintaan yang dikirim dari alamat email akun ini.');

        $this->modalSubmitActionLabel('Hapus akun');

        $this->successNotificationTitle('Akun dihapus');

        $this->successRedirectUrl(fn (): string => UserResource::getUrl('index'));

        // Deleting the admin would lock everyone out of the panel.
        $this->hidden(fn (User $record): bool => $record->isAdmin());

        $this->action(function (User $record): void {
            resolve(DeleteAccount::class)->handle($record);

            $this->success();
        });
    }
}

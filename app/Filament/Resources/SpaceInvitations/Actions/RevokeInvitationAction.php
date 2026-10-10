<?php

namespace App\Filament\Resources\SpaceInvitations\Actions;

use App\Enums\InvitationStatus;
use App\Models\SpaceInvitation;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RevokeInvitationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'revoke';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cabut');

        $this->icon(Heroicon::OutlinedNoSymbol);

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalHeading('Cabut undangan');

        $this->modalDescription('Kode ini tidak bisa dipakai lagi. Pembuatnya bisa membuat kode baru dari aplikasi.');

        $this->modalSubmitActionLabel('Cabut');

        $this->successNotificationTitle('Undangan dicabut');

        $this->failureNotificationTitle('Undangan ini sudah tidak aktif');

        $this->visible(fn (SpaceInvitation $record): bool => $record->status === InvitationStatus::Active);

        $this->action(function (SpaceInvitation $record): void {
            // Only a code that is still active is changed, so one that was
            // accepted while the confirmation was open stays accepted.
            $revoked = SpaceInvitation::query()
                ->whereKey($record->getKey())
                ->active()
                ->update(['revoked_at' => now()]);

            if ($revoked === 0) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}

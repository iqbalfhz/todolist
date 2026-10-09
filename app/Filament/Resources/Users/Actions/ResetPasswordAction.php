<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class ResetPasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resetPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Reset kata sandi');

        $this->icon(Heroicon::OutlinedKey);

        $this->color('warning');

        $this->modalDescription('Semua token pengguna ini dicabut, jadi setiap perangkatnya harus masuk lagi dengan kata sandi baru.');

        $this->modalSubmitActionLabel('Reset kata sandi');

        $this->successNotificationTitle('Kata sandi diatur ulang');

        $this->schema([
            TextInput::make('password')
                ->label('Kata sandi baru')
                ->password()
                ->revealable()
                ->required()
                ->rule(Password::defaults()),
        ]);

        $this->action(function (array $data, User $record): void {
            DB::transaction(function () use ($data, $record): void {
                $record->update(['password' => $data['password']]);
                $record->tokens()->delete();
            });

            $this->success();
        });
    }
}

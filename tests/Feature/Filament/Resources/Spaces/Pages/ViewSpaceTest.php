<?php

use App\Filament\Resources\Spaces\Pages\ViewSpace;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Livewire\Livewire;

it('shows the space with its members and their roles', function () {
    actingAsAdmin();
    $owner = User::factory()->create(['name' => 'Iqbal', 'email' => 'iqbal@contoh.test']);
    $partner = User::factory()->create(['name' => 'Pasangan Iqbal', 'email' => 'pasangan@contoh.test']);
    $space = sharedSpaceBetween($owner, $partner);

    $page = Livewire::test(ViewSpace::class, ['record' => $space->id]);

    $page->assertOk()
        ->assertSchemaStateSet(['name' => 'Ruang Bersama', 'timezone' => 'Asia/Jakarta'])
        ->assertSeeText(['Bersama', 'Iqbal', 'iqbal@contoh.test', 'Pemilik', 'Pasangan Iqbal', 'pasangan@contoh.test']);
});

it('shows nothing of what the members keep in the space', function () {
    actingAsAdmin();
    $space = Space::factory()->create();
    $list = TaskList::factory()->for($space)->create(['name' => 'Belanja bulanan']);
    Task::factory()->for($list)->create(['title' => 'Beli galon', 'notes' => 'Yang isi ulang']);
    $goal = SavingsGoal::factory()->for($space)->create(['name' => 'Dana Liburan']);
    SavingsEntry::factory()->for($goal)->create(['note' => 'Dari gajian']);

    $page = Livewire::test(ViewSpace::class, ['record' => $space->id]);

    $page->assertOk()->assertDontSeeText(['Belanja bulanan', 'Beli galon', 'Yang isi ulang', 'Dana Liburan', 'Dari gajian']);
});

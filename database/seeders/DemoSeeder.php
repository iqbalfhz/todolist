<?php

namespace Database\Seeders;

use App\Actions\AcceptInvitation;
use App\Actions\CreateComment;
use App\Actions\CreatePersonalSpace;
use App\Actions\CreateReaction;
use App\Actions\CreateSavingsEntry;
use App\Actions\CreateTask;
use App\Actions\IssueInvitation;
use App\Actions\UpdateTask;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class DemoSeeder extends Seeder
{
    /**
     * The email of the demo account that sent the invitation.
     */
    public const USER_EMAIL = 'aku@contoh.test';

    /**
     * The email of the demo account that accepted the invitation.
     */
    public const PARTNER_EMAIL = 'pasangan@contoh.test';

    public function __construct(
        private CreatePersonalSpace $createPersonalSpace,
        private IssueInvitation $issueInvitation,
        private AcceptInvitation $acceptInvitation,
        private CreateTask $createTask,
        private UpdateTask $updateTask,
        private CreateSavingsEntry $createSavingsEntry,
        private CreateComment $createComment,
        private CreateReaction $createReaction,
    ) {}

    /**
     * Create two partnered demo accounts with lists, tasks, and savings to try the app with.
     *
     * Everything goes through the actions the API uses, so the data looks
     * the way the app itself would have produced it.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new LogicException('DemoSeeder membuat akun dengan kata sandi bawaan dan hanya boleh dijalankan di lingkungan lokal.');
        }

        if (User::query()->where('email', self::USER_EMAIL)->exists()) {
            $this->command?->warn('Akun contoh sudah ada, jadi data contoh tidak dibuat lagi.');

            return;
        }

        DB::transaction(function (): void {
            $user = $this->register('Aku', self::USER_EMAIL, 'mawar');
            $partner = $this->register('Pasangan', self::PARTNER_EMAIL, 'ungu');

            [$invitation] = $this->issueInvitation->handle($user);
            [$sharedSpace] = $this->acceptInvitation->handle($partner, $invitation->code);

            $this->fillPersonalSpace($user);
            $this->fillSharedSpace($sharedSpace, $user, $partner);
        });
    }

    /**
     * Create an account the way registration does, with its personal space.
     */
    private function register(string $name, string $email, string $color): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'color' => $color,
        ]);

        $this->createPersonalSpace->handle($user);

        return $user;
    }

    /**
     * Add a few private tasks and a private savings goal.
     */
    private function fillPersonalSpace(User $user): void
    {
        $space = $user->personalSpace();
        $list = $space->taskLists()->sole();
        $today = CarbonImmutable::now($space->timezone);

        $this->task($user, $list, [
            'title' => 'Perpanjang SIM',
            'due_date' => $today->addDays(3)->toDateString(),
        ]);
        $this->task($user, $list, [
            'title' => 'Olahraga pagi',
            'completed_at' => now()->toIso8601String(),
        ]);

        $laptop = $this->goal($user, $space, [
            'name' => 'Laptop baru',
            'emoji' => '💻',
            'target_amount' => 15_000_000,
        ]);
        $this->deposit($user, $laptop, 3_000_000, $today->subDays(10), 'Tabungan awal');
    }

    /**
     * Add lists, tasks for each of the partners and for both, and two savings goals, one of them achieved.
     */
    private function fillSharedSpace(Space $space, User $user, User $partner): void
    {
        $today = CarbonImmutable::now($space->timezone);
        $tasks = $space->taskLists()->sole();
        $shopping = $this->taskList($user, $space, ['name' => 'Belanja', 'emoji' => '🛒', 'color' => 'toska', 'position' => 2048]);
        $home = $this->taskList($partner, $space, ['name' => 'Rumah', 'emoji' => '🏠', 'color' => 'biru', 'position' => 3072]);

        $this->task($user, $tasks, [
            'title' => 'Pesan tiket kereta',
            'notes' => 'Berangkat Jumat sore, pulang Minggu malam.',
            'due_date' => $today->addDays(5)->toDateString(),
            'due_time' => '09:00',
            'assignee_ids' => [$user->id, $partner->id],
        ]);
        $this->task($user, $shopping, [
            'title' => 'Beli galon',
            'due_date' => $today->toDateString(),
            'due_time' => '17:00',
            'assignee_ids' => [$partner->id],
        ]);
        $soap = $this->task($user, $shopping, [
            'title' => 'Beli sabun cuci',
            'assignee_ids' => [$partner->id],
        ]);
        $this->updateTask->handle($partner, $soap, ['completed_at' => now()->toIso8601String()]);
        $this->comment($user, $soap, 'Makasih ya, sudah dibelikan!');
        $this->comment($partner, $soap, 'Sama-sama. Sekalian beli pewangi juga.');
        $this->react($user, $soap, '❤️');
        $this->task($partner, $home, [
            'title' => 'Bayar listrik',
            'due_date' => $today->addDay()->toDateString(),
            'priority' => 3,
            'assignee_ids' => [$user->id],
        ]);
        $this->task($partner, $home, [
            'title' => 'Bersihkan kulkas',
            'assignee_ids' => [$user->id, $partner->id],
        ]);

        $holiday = $this->goal($user, $space, [
            'name' => 'Dana Liburan',
            'emoji' => '✈️',
            'target_amount' => 10_000_000,
            'target_date' => $today->addMonths(8)->toDateString(),
        ]);
        $payday = $this->deposit($user, $holiday, 2_000_000, $today->subDays(20), 'Gajian');
        $this->react($partner, $payday, '🎉');
        $this->deposit($partner, $holiday, 1_500_000, $today->subDays(6));

        $gift = $this->goal($partner, $space, [
            'name' => 'Kado Ulang Tahun Ibu',
            'emoji' => '🎁',
            'target_amount' => 500_000,
        ]);
        $this->deposit($partner, $gift, 300_000, $today->subDays(12));
        $this->deposit($user, $gift, 200_000, $today->subDays(2), 'Pelunasan');
    }

    /**
     * Create a task list in the space.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function taskList(User $creator, Space $space, array $attributes): TaskList
    {
        return $space->taskLists()->create([...$attributes, 'created_by' => $creator->id]);
    }

    /**
     * Create a task at the end of the list.
     *
     * @param  array<string, mixed>  $data
     */
    private function task(User $creator, TaskList $list, array $data): Task
    {
        return $this->createTask->handle($creator, $this->newId(), [
            ...$data,
            'task_list_id' => $list->id,
            'position' => 1024 * ($list->tasks()->count() + 1),
        ]);
    }

    /**
     * Create a savings goal in rupiah in the space.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function goal(User $creator, Space $space, array $attributes): SavingsGoal
    {
        return $space->savingsGoals()->create([...$attributes, 'currency' => 'IDR', 'created_by' => $creator->id]);
    }

    /**
     * Record a deposit towards the goal.
     */
    private function deposit(User $depositor, SavingsGoal $goal, int $amount, CarbonImmutable $date, ?string $note = null): SavingsEntry
    {
        return $this->createSavingsEntry->handle($depositor, $this->newId(), [
            'savings_goal_id' => $goal->id,
            'amount' => $amount,
            'entry_date' => $date->toDateString(),
            'note' => $note,
        ]);
    }

    /**
     * Comment on a task or a deposit in the shared space.
     */
    private function comment(User $author, Task|SavingsEntry $subject, string $body): void
    {
        $this->createComment->handle($author, $this->newId(), [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'body' => $body,
        ]);
    }

    /**
     * React to a task or a deposit in the shared space.
     */
    private function react(User $author, Task|SavingsEntry $subject, string $emoji): void
    {
        $this->createReaction->handle($author, $this->newId(), [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'emoji' => $emoji,
        ]);
    }

    /**
     * Generate an ID the way the phone does: a lowercase ULID.
     */
    private function newId(): string
    {
        return Str::lower((string) Str::ulid());
    }
}

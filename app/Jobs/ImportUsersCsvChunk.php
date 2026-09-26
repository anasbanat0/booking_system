<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportUsersCsvChunk implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public array $rows,
        public array $locations,
        public int $actorId,
        public bool $canManageAllBranches,
        public ?int $actorBranchId,
        public int $rowOffset = 0,
    ) {}

    public function handle(): void
    {
        $passwordHashes = [];
        $imported = 0;
        $skipped = 0;

        foreach ($this->rows as $index => $data) {
            $name = trim((string) ($data['name'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $phone = trim((string) ($data['phone'] ?? ''));

            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            $allowedRoles = $this->canManageAllBranches ? ['student', 'staff', 'admin'] : ['student'];
            $requestedRole = strtolower(trim((string) ($data['role'] ?? 'student')));
            $role = in_array($requestedRole, $allowedRoles, true) ? $requestedRole : 'student';

            $existingUser = User::withTrashed()->where(function ($query) use ($email, $phone) {
                $query->where('email', $email);

                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }
            })->first();

            if ($existingUser) {
                $skipped++;

                continue;
            }

            $branchName = strtolower(trim((string) ($data['branch'] ?? '')));
            $branchId = $this->canManageAllBranches
                ? ($this->locations[$branchName] ?? null)
                : $this->actorBranchId;

            if ($role !== 'admin' && ! $branchId) {
                $skipped++;

                continue;
            }

            $plainPassword = trim((string) ($data['password'] ?? '')) ?: 'password';
            $passwordHashes[$plainPassword] ??= Hash::make($plainPassword);

            try {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'role' => $role,
                    'booking_location_id' => $role === 'admin' ? null : $branchId,
                    'password' => $passwordHashes[$plainPassword],
                ]);
            } catch (\Throwable $exception) {
                $skipped++;
                Log::warning('CSV user row could not be imported.', [
                    'email' => $email,
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }

            SendPasswordSetupLink::dispatch($user->id, true)
                ->delay(now()->addSeconds(($this->rowOffset + $index) * 8));

            ActivityLog::record('user_imported', 'User imported', $user->name.' was imported from CSV.', [
                'actor_id' => $this->actorId,
                'user_id' => $user->id,
                'properties' => ['role' => $user->role],
            ]);

            $imported++;
        }

        Log::info('CSV user import chunk completed.', [
            'actor_id' => $this->actorId,
            'row_offset' => $this->rowOffset,
            'rows' => count($this->rows),
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }
}

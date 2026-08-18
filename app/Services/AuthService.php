<?php

namespace App\Services;

use App\Exceptions\UnauthorizedLoginException;
use App\Models\Admin;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserFormatterService $userFormatter,
    ) {}

    public function login(string $account, string $password): array
    {
        $authenticatable = $this->findAuthenticatable($account);

        if ($authenticatable === null || ! Hash::check($password, $authenticatable->getAuthPassword())) {
            throw new UnauthorizedLoginException();
        }

        $token = $authenticatable->createToken('auth')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userFormatter->format($authenticatable),
        ];
    }

    public function logout(Authenticatable $authenticatable): void
    {
        $token = $authenticatable->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    public function me(Authenticatable $authenticatable): array
    {
        return $this->userFormatter->format($authenticatable);
    }

    /**
     * Lookup order: admins.account → teachers.account → students.email
     */
    private function findAuthenticatable(string $account): ?Authenticatable
    {
        $admin = Admin::query()->where('account', $account)->first();
        if ($admin !== null) {
            return $admin;
        }

        $teacher = Teacher::query()->where('account', $account)->first();
        if ($teacher !== null) {
            return $teacher;
        }

        return Student::query()->where('email', $account)->first();
    }
}

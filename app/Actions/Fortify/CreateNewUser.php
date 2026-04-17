<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->registrationRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $firstName = trim($input['first_name']);
            $lastName = trim($input['last_name']);
            $fullName = trim("{$firstName} {$lastName}");

            $customer = Customer::create([
                'code' => 'CUST-'.strtoupper(Str::random(6)),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $input['email'],
            ]);

            $user = User::create([
                'name' => $fullName,
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            Role::findOrCreate(UserRole::Customer->value, 'web');
            $user->assignRole(UserRole::Customer->value);
            $user->customers()->attach($customer->id);

            return $user;
        });
    }
}

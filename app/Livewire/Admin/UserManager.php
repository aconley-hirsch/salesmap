<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Users')]
class UserManager extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $isAdmin = false;

    public ?string $setupUrl = null;

    public ?string $setupUrlUserName = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'isAdmin' => 'boolean',
        ];
    }

    public function createUser(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make(Str::random(40)),
            'is_admin' => $this->isAdmin,
            'email_verified_at' => now(),
            'password_set_at' => null,
        ]);

        $this->setupUrl = $this->generateSetupUrl($user);
        $this->setupUrlUserName = $this->name;
        $this->reset(['name', 'email', 'isAdmin']);
    }

    public function dismissUrl(): void
    {
        $this->setupUrl = null;
        $this->setupUrlUserName = null;
    }

    public function toggleAdmin(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['is_admin' => ! $user->is_admin]);
    }

    public function resetPassword(int $id): void
    {
        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make(Str::random(40)),
            'password_set_at' => null,
        ]);

        $this->setupUrl = $this->generateSetupUrl($user);
        $this->setupUrlUserName = $user->name;
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.user-manager');
    }

    private function generateSetupUrl(User $user): string
    {
        return URL::signedRoute('password.setup', ['user' => $user->id]);
    }
}

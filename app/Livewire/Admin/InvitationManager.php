<?php

namespace App\Livewire\Admin;

use App\Models\Invitation;
use App\Notifications\InvitationCreated;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manage Invitations')]
class InvitationManager extends Component
{
    use WithPagination;

    public string $email = '';

    public string $name = '';

    public string $search = '';

    public string $statusFilter = 'all'; // all, pending, accepted, expired, revoked

    protected $rules = [
        'email' => 'required|email|unique:users,email',
        'name' => 'required|string|max:255',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sendInvitation()
    {
        $this->validate();

        // Check for existing pending invitation
        $existing = Invitation::where('email', $this->email)
            ->pending()
            ->first();

        if ($existing) {
            $this->addError('email', 'An invitation has already been sent to this email address.');

            return;
        }

        // Create invitation
        $invitation = Invitation::create([
            'email' => $this->email,
            'name' => $this->name,
            'invited_by' => auth()->id(),
        ]);

        // Send notification
        Notification::route('mail', $invitation->email)
            ->notify(new InvitationCreated($invitation));

        // Reset form
        $this->reset(['email', 'name']);

        session()->flash('success', 'Invitation sent successfully to '.$invitation->email);
    }

    public function resendInvitation(int $id)
    {
        $invitation = Invitation::findOrFail($id);

        if (! $invitation->isPending()) {
            session()->flash('error', 'Only pending invitations can be resent.');

            return;
        }

        // Update expiration
        $invitation->update(['expires_at' => now()->addDays(7)]);

        // Resend notification
        Notification::route('mail', $invitation->email)
            ->notify(new InvitationCreated($invitation));

        session()->flash('success', 'Invitation resent successfully.');
    }

    public function revokeInvitation(int $id)
    {
        $invitation = Invitation::findOrFail($id);
        $invitation->revoke();

        session()->flash('success', 'Invitation revoked successfully.');
    }

    #[Computed]
    public function invitations()
    {
        return Invitation::query()
            ->with('inviter')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('email', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                match ($this->statusFilter) {
                    'pending' => $query->pending(),
                    'accepted' => $query->whereNotNull('accepted_at'),
                    'expired' => $query->whereNull('accepted_at')
                        ->whereNull('revoked_at')
                        ->where('expires_at', '<', now()),
                    'revoked' => $query->whereNotNull('revoked_at'),
                };
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.invitation-manager');
    }
}

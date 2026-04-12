<?php

namespace App\Livewire\Admin;

use App\Models\KeyContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Key Contacts')]
class KeyContactManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'title' => '',
        'email' => '',
        'phone' => '',
        'notes' => '',
        'group_name' => 'Leaders',
        'sort_order' => 0,
    ];

    protected function rules(): array
    {
        return [
            'form.name' => 'required|string|max:100',
            'form.title' => 'nullable|string|max:150',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.notes' => 'nullable|string|max:1000',
            'form.group_name' => 'required|string|max:100',
            'form.sort_order' => 'integer|min:0',
        ];
    }

    #[Computed]
    public function groups(): array
    {
        return KeyContact::active()
            ->ordered()
            ->get()
            ->groupBy('group_name')
            ->all();
    }

    #[Computed]
    public function groupNames(): array
    {
        return KeyContact::distinct()
            ->orderBy('group_order')
            ->pluck('group_name')
            ->all();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $contact = KeyContact::findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'name' => $contact->name,
            'title' => $contact->title ?? '',
            'email' => $contact->email ?? '',
            'phone' => $contact->phone ?? '',
            'notes' => $contact->notes ?? '',
            'group_name' => $contact->group_name,
            'sort_order' => $contact->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = array_merge($this->form, [
            'title' => $this->form['title'] ?: null,
            'email' => $this->form['email'] ?: null,
            'phone' => $this->form['phone'] ?: null,
            'notes' => $this->form['notes'] ?: null,
        ]);

        // Set group_order based on existing groups
        $existingOrder = KeyContact::where('group_name', $data['group_name'])
            ->value('group_order');
        $data['group_order'] = $existingOrder ?? KeyContact::max('group_order') + 1;

        if ($this->editingId) {
            KeyContact::where('id', $this->editingId)->update($data);
        } else {
            KeyContact::create($data);
        }

        $this->resetForm();
        $this->showForm = false;

        $this->dispatch('flash', [
            'type' => 'success',
            'message' => $this->editingId ? 'Contact updated.' : 'Contact created.',
        ]);
    }

    public function delete(int $id): void
    {
        KeyContact::findOrFail($id)->delete();

        $this->dispatch('flash', [
            'type' => 'success',
            'message' => 'Contact deleted.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.key-contact-manager');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'title' => '',
            'email' => '',
            'phone' => '',
            'notes' => '',
            'group_name' => 'Leaders',
            'sort_order' => 0,
        ];
        $this->resetValidation();
    }
}

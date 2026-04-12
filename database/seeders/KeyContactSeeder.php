<?php

namespace Database\Seeders;

use App\Models\KeyContact;
use Illuminate\Database\Seeder;

class KeyContactSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = [
            // Leaders (group_order: 0)
            ['name' => 'Brian Glandon', 'title' => 'National Sales Director', 'email' => 'bglandon@hirschsecure.com', 'group_name' => 'Leaders', 'group_order' => 0, 'sort_order' => 0],
            ['name' => 'Dan Murray', 'title' => 'Regional Sales Director, West', 'email' => 'dmurray@hirschsecure.com', 'group_name' => 'Leaders', 'group_order' => 0, 'sort_order' => 1],
            ['name' => 'Christopher Cosentino', 'title' => 'Director of Sales Engineering', 'email' => 'ccosentino@hirschsecure.com', 'group_name' => 'Leaders', 'group_order' => 0, 'sort_order' => 2],
            ['name' => 'Ryane Burke', 'title' => 'Marketing Director', 'email' => 'rburke@hirschsecure.com', 'group_name' => 'Leaders', 'group_order' => 0, 'sort_order' => 3],

            // Fed Team (group_order: 1)
            ['name' => 'Tim Britell', 'title' => 'Federal Sales Director', 'email' => 'tbritell@hirschsecure.com', 'group_name' => 'Fed Team', 'group_order' => 1, 'sort_order' => 0],
            ['name' => 'Malcolm Ceasar', 'title' => 'Program Manager, Federal', 'email' => 'mceasar@hirschsecure.com', 'notes' => 'Treasury, USSS, FBI, IRS, BEP, ATFB', 'group_name' => 'Fed Team', 'group_order' => 1, 'sort_order' => 1],
            ['name' => 'Patrick Hennessy', 'title' => 'Program Manager, Federal', 'email' => 'phennessy@hirschsecure.com', 'notes' => 'USMS, CBP, TSA, HUD, DCSA, USAO, FLETC, NIST, NRC', 'group_name' => 'Fed Team', 'group_order' => 1, 'sort_order' => 2],
            ['name' => 'Chris Gillolfo', 'title' => 'Federal Business Development', 'email' => 'cgillolfo@hirschsecure.com', 'group_name' => 'Fed Team', 'group_order' => 1, 'sort_order' => 3],
            ['name' => 'Jim Hansen', 'title' => 'Federal Business Development', 'email' => 'jhansen@hirschsecure.com', 'group_name' => 'Fed Team', 'group_order' => 1, 'sort_order' => 4],

            // Marketing/Sales Operations (group_order: 2)
            ['name' => 'McKenna Boose', 'title' => 'Events & Partner Marketing', 'email' => 'mboose@hirschsecure.com', 'group_name' => 'Marketing/Sales Operations', 'group_order' => 2, 'sort_order' => 0],
            ['name' => 'Mashal Nawabi', 'title' => 'Manager, Inside Sales', 'email' => 'mnawabi@hirschsecure.com', 'group_name' => 'Marketing/Sales Operations', 'group_order' => 2, 'sort_order' => 1],
            ['name' => 'Aaron Garner', 'title' => 'Sales Development Representative', 'email' => 'agarner@hirschsecure.com', 'group_name' => 'Marketing/Sales Operations', 'group_order' => 2, 'sort_order' => 2],

            // Shared Inboxes (group_order: 3)
            ['name' => 'Sales', 'email' => 'sales@hirschsecure.com', 'group_name' => 'Shared Inboxes', 'group_order' => 3, 'sort_order' => 0],
            ['name' => 'Training', 'email' => 'training@hirschsecure.com', 'group_name' => 'Shared Inboxes', 'group_order' => 3, 'sort_order' => 1],
            ['name' => 'Orders', 'email' => 'orders@hirschsecure.com', 'group_name' => 'Shared Inboxes', 'group_order' => 3, 'sort_order' => 2],
            ['name' => 'Partner Portal', 'email' => 'partnerportal@hirschsecure.com', 'group_name' => 'Shared Inboxes', 'group_order' => 3, 'sort_order' => 3],
        ];

        foreach ($contacts as $contact) {
            KeyContact::firstOrCreate(
                ['email' => $contact['email']],
                array_merge(['is_active' => true], $contact)
            );
        }
    }
}

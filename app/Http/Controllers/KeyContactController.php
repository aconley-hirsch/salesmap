<?php

namespace App\Http\Controllers;

use App\Models\KeyContact;
use Illuminate\View\View;

class KeyContactController extends Controller
{
    public function index(): View
    {
        $groups = KeyContact::active()
            ->ordered()
            ->get()
            ->groupBy('group_name');

        return view('key-contacts', [
            'groups' => $groups,
        ]);
    }
}

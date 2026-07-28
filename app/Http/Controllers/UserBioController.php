<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserBioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserBioController extends Controller
{
    public function update(UpdateUserBioRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return back()->with('success', 'Profile bio updated successfully.');
    }
}

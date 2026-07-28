<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileImageRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserImageController extends Controller
{
    public function update(UpdateProfileImageRequest $request, User $user): RedirectResponse
    {
        $path = $request->file('image')->store('images', 'public');
        $newFilename = basename($path);
        $oldFilename = $user->image;

        try {
            $user->update(['image' => $newFilename]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            report($exception);

            return back()->with('error', 'The profile image could not be updated.');
        }

        if (
            $oldFilename
            && $oldFilename !== 'default.svg'
            && basename($oldFilename) === $oldFilename
        ) {
            Storage::disk('public')->delete('images/'.$oldFilename);
        }

        return back()->with('success', 'Profile image updated successfully.');
    }
}

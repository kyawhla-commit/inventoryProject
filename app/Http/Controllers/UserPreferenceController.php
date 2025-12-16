<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    /**
     * Update user's theme preference
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
        ]);

        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->theme_preference = $validated['theme'];
            $user->save();
        }

        return response()->json([
            'success' => true,
            'theme' => $validated['theme'],
        ]);
    }

    /**
     * Update user's language preference
     */
    public function updateLanguage(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|in:en,mm',
        ]);

        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->language = $validated['language'];
            $user->save();
        }

        session(['locale' => $validated['language']]);

        return response()->json([
            'success' => true,
            'language' => $validated['language'],
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences()
    {
        $preferences = [
            'theme' => 'auto',
            'language' => 'en',
        ];

        if (Auth::check()) {
            $preferences = [
                'theme' => Auth::user()->theme_preference ?? 'auto',
                'language' => Auth::user()->language ?? 'en',
            ];
        }

        return response()->json($preferences);
    }
}

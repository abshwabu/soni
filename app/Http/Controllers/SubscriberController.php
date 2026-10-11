<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    /**
     * Store a new newsletter subscriber.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email_or_phone' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        Subscriber::firstOrCreate(
            ['email_or_phone' => trim($validated['email_or_phone'])],
            ['source' => $validated['source'] ?? 'footer_newsletter']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for subscribing to Soni Perfume!',
            ]);
        }

        return back()->with('success', 'Thank you for subscribing to Soni Perfume!');
    }
}

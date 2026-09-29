<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContactController extends Controller
{
    /** Keep old route tables working while shared-host PHP workers refresh. */
    public function show(): View
    {
        return app(SiteController::class)->page('contact');
    }

    /** Store submissions from the CMS-rendered contact page. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'interest' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot. Real people never see or fill this; bots usually do.
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Submission rejected.',
        ], [
            'name' => 'your name',
            'email' => 'email address',
            'interest' => 'service',
        ]);

        $throttleKey = 'contact:'.strtolower((string) $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'message' => 'Too many messages sent. Please wait a minute and try again.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        Message::create([
            ...$data,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        // No email is sent yet — SMTP is unconfigured on staging. Stored
        // in the inbox only. See docs/CHANGELOG.md open items.
        return redirect()
            ->route('contact')
            ->with('success', 'Thank you — your message has been received. We will be in touch shortly.');
    }
}

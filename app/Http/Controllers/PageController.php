<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessage;
use App\Support\SafeNotifier;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            // Honeypot: a real visitor never fills this in (it's visually
            // hidden), a bot filling every field usually does.
            'company_website' => ['prohibited'],
        ], [
            'company_website.prohibited' => 'Something about that submission looked automated — please try again.',
        ]);

        unset($data['company_website']);

        $message = ContactMessage::create($data);

        foreach (User::where('is_admin', true)->get() as $admin) {
            SafeNotifier::send($admin, new NewContactMessage($message));
        }

        return back()->with('success', "Thanks — we've received your message and will get back to you soon.");
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }
}

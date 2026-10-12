<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use App\Services\TelegramService;
use App\Support\ContactUrlHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Display the About page.
     */
    public function about(): Response
    {
        $page = Page::where('slug', 'about')->published()->first();

        return Inertia::render('About', [
            'page' => $page,
            'storyImages' => [
                [
                    'url' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=1000&q=80',
                    'caption' => 'The Soni Perfume Boutique Experience in Addis Ababa',
                    'alt' => 'Luxury perfume bottles displayed in Addis Ababa boutique',
                ],
                [
                    'url' => 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=1000&q=80',
                    'caption' => 'Authentic Designer Fragrances & Precious Oriental Attars',
                    'alt' => 'Artisanal perfume glass bottles with golden caps',
                ],
                [
                    'url' => 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=1000&q=80',
                    'caption' => 'Bespoke Fragrance Consultations & Same-Day Dispatch',
                    'alt' => 'Elegantly packaged luxury fragrance gift box',
                ],
            ],
            'seo' => [
                'title' => $page?->meta_title ?: 'About Our Fragrance Boutique | Soni Perfume Addis Ababa',
                'description' => $page?->meta_description ?: 'Discover our uncompromising authenticity guarantee, Ethiopian artisan heritage, and premier fragrance destination in Bole, Addis Ababa.',
                'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=1000&q=80',
                'url' => route('about'),
            ],
        ]);
    }

    /**
     * Display the Contact & Location page.
     */
    public function contact(): Response
    {
        $settings = Setting::pluck('value', 'key')->all();

        return Inertia::render('Contact', [
            'storeInfo' => [
                'name' => $settings['store_name'] ?? 'Soni Perfume',
                'address' => $settings['store_address'] ?? 'Bole Medhanialem, Addis Ababa, Ethiopia',
                'phone' => $settings['store_phone'] ?? '+251 91 100 0000',
                'altPhone' => $settings['store_alt_phone'] ?? '+251 92 200 0000',
                'whatsapp' => $settings['store_whatsapp'] ?? $settings['store_phone'] ?? '+251 91 100 0000',
                'telegram' => $settings['telegram_url'] ?? $settings['store_telegram'] ?? 'https://t.me/soniperfume_admin',
                'whatsapp_url' => ContactUrlHelper::formatWhatsAppUrl($settings['store_whatsapp'] ?? $settings['store_phone'] ?? '+251 91 100 0000'),
                'telegram_url' => ContactUrlHelper::formatTelegramUrl($settings['telegram_url'] ?? $settings['store_telegram'] ?? 'soniperfume_admin'),
                'email' => $settings['store_email'] ?? 'concierge@soniperfume.com',
                'hours' => $settings['opening_hours'] ?? 'Mon–Sat: 9:00 AM – 8:00 PM | Sun: 10:00 AM – 6:00 PM',
                'geo' => [
                    'lat' => 8.9978,
                    'lng' => 38.7888,
                ],
            ],
            'seo' => [
                'title' => 'Contact Us & Boutique Location | Soni Perfume Bole Addis Ababa',
                'description' => 'Visit our boutique in Bole Medhanialem or contact our fragrance concierge via phone, WhatsApp, or Telegram for fast same-day delivery in Addis Ababa.',
                'url' => route('contact'),
            ],
        ]);
    }

    /**
     * Handle contact form submission and notify admin via Telegram.
     */
    public function submitContact(Request $request, TelegramService $telegramService): RedirectResponse
    {
        // Rate limit: maximum 5 messages per IP per hour
        $ip = $request->ip();
        $rateKey = 'contact-submit:' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()->with('error', "Too many messages sent. Please wait {$seconds} seconds before trying again.");
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        RateLimiter::hit($rateKey, 3600);

        // Normalize phone for direct contact button
        $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone']);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '251' . substr($cleanPhone, 1);
        }

        $adminChatIds = $telegramService->getAllowedAdminChatIds();
        if (! empty($adminChatIds) && ! empty($telegramService->getBotToken())) {
            $timeAddis = now('Africa/Addis_Ababa')->format('M d, Y h:i A');
            $nameEsc = $telegramService->escape($validated['name']);
            $phoneEsc = $telegramService->escape($validated['phone']);
            $emailEsc = $telegramService->escape($validated['email'] ?? 'Not provided');
            $subjectEsc = $telegramService->escape($validated['subject'] ?: 'General Customer Inquiry');
            $msgEsc = $telegramService->escape($validated['message']);

            $text = <<<TEXT
📩 <b>New Contact Form Inquiry</b>
━━━━━━━━━━━━━━━━━━━━
👤 <b>Name:</b> {$nameEsc}
📞 <b>Phone:</b> {$phoneEsc}
📧 <b>Email:</b> {$emailEsc}
📋 <b>Subject:</b> {$subjectEsc}

💬 <b>Message:</b>
{$msgEsc}
━━━━━━━━━━━━━━━━━━━━
🕒 <b>Sent:</b> {$timeAddis}
TEXT;

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '💬 Chat on WhatsApp', 'url' => "https://wa.me/{$cleanPhone}?text=" . urlencode("Hello {$validated['name']}, regarding your message to Soni Perfume:")],
                    ],
                ],
            ];

            foreach ($adminChatIds as $chatId) {
                $telegramService->sendMessage($chatId, $text, $replyMarkup);
            }
        }

        return back()->with('success', 'Thank you! Your message has been sent to our concierge team. We will contact you shortly.');
    }

    /**
     * Display a standard CMS page (e.g. delivery-returns, privacy, terms).
     */
    public function show(string $slug): Response
    {
        $page = Page::where('slug', $slug)->published()->firstOrFail();

        return Inertia::render('Page', [
            'page' => $page,
            'seo' => [
                'title' => $page->meta_title ?: "{$page->title} | Soni Perfume Addis Ababa",
                'description' => $page->meta_description ?: strip_tags(substr($page->content, 0, 160)),
                'url' => url()->current(),
            ],
        ]);
    }
}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FAF7F2">

        <title inertia>{{ config('app.name', 'Soni Perfume') }}</title>

        <!-- Preconnect & DNS-Prefetch for Speed -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Global Structured Data (JSON-LD Organization & LocalBusiness) -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "PerfumeStore",
            "name": "Soni Perfume",
            "image": "{{ config('app.url') }}/images/logo.png",
            "@id": "{{ config('app.url') }}",
            "url": "{{ config('app.url') }}",
            "telephone": "{{ config('services.store.phone', '+251911000000') }}",
            "priceRange": "$$$",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Bole Medhanialem",
                "addressLocality": "Addis Ababa",
                "addressCountry": "ET"
            },
            "geo": {
                "@type": "GeoCoordinates",
                "latitude": 8.9978,
                "longitude": 38.7888
            },
            "openingHoursSpecification": [
                {
                    "@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                    "opens": "09:00",
                    "closes": "20:00"
                },
                {
                    "@type": "OpeningHoursSpecification",
                    "dayOfWeek": ["Sunday"],
                    "opens": "10:00",
                    "closes": "18:00"
                }
            ],
            "sameAs": [
                "https://t.me/soniperfume",
                "https://instagram.com/soniperfume"
            ]
        }
        </script>

        <!-- Analytics Integration (Optional) -->
        @if(config('services.analytics.plausible_domain'))
            <script defer data-domain="{{ config('services.analytics.plausible_domain') }}" src="https://plausible.io/js/script.js"></script>
            <script>window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) };</script>
        @endif

        @if(config('services.analytics.ga4_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.ga4_id') }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ config('services.analytics.ga4_id') }}');
            </script>
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased text-brand-charcoal bg-brand-bg min-h-screen selection:bg-brand-gold/30 selection:text-brand-charcoal">
        @inertia
    </body>
</html>

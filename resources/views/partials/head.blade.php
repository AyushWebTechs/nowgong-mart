<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#ffffff">
<meta name="description" content="{{ $store->get('store_tagline') }}">
{{-- Turbo: animated page transitions, and smooth in-place refreshes that keep scroll position. --}}
<meta name="view-transition" content="same-origin">
<meta name="turbo-refresh-method" content="morph">
<meta name="turbo-refresh-scroll" content="preserve">
<title>@hasSection('title')@yield('title') · @endif{{ $store->get('store_name') }}</title>
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])

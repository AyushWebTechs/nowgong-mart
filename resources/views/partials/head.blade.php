<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#176b45">
<meta name="description" content="{{ $store->get('store_tagline') }}">
<title>@hasSection('title')@yield('title') · @endif{{ $store->get('store_name') }}</title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
@vite(['resources/css/app.css', 'resources/js/app.js'])

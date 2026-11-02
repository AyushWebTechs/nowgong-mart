@extends('layouts.app')

@section('title', $title)

@section('content')
    <article class="card mx-auto max-w-3xl space-y-4 p-6 text-sm leading-relaxed text-gray-700">
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Template text. The store owner must review this page, preferably with a legal adviser, before launch.</p>
        @yield('page')
        <p class="text-xs text-gray-500">Last updated: {{ now()->format('d M Y') }}</p>
    </article>
@endsection

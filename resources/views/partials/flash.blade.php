{{-- Success/error messages become toasts (see resources/js/ui.js); validation errors stay inline. --}}
@if (session('success'))
    <div data-toast="success" role="status" class="sr-only">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div data-toast="error" role="alert" class="sr-only">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 flex gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
        <x-icon name="alert" class="mt-0.5 size-5 text-rose-500" />
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Search overlay with live suggestions; behaviour in resources/js/search.js. --}}
<div class="fixed inset-0 z-50" hidden data-search
     data-suggest-url="{{ route('search.suggest') }}"
     data-trending='@json($trendingSearches)'
     data-categories='@json($navCategories->map(fn ($c) => ['name' => $c->name, 'url' => route('categories.show', $c)])->values())'>
    <div class="absolute inset-0 bg-gray-950/40 opacity-0 backdrop-blur-sm transition-opacity duration-200" data-search-backdrop data-search-close></div>
    <div class="relative mx-auto flex h-full max-w-2xl translate-y-3 flex-col overflow-hidden bg-white opacity-0 transition duration-200 ease-out md:mt-20 md:h-auto md:max-h-[72vh] md:rounded-3xl md:shadow-2xl" data-search-panel>
        <form action="{{ route('products.index') }}" method="GET" role="search" class="flex items-center gap-2 border-b border-gray-100 p-3" data-search-form>
            <button type="button" class="grid size-10 shrink-0 place-items-center rounded-xl text-gray-600 hover:bg-gray-100" data-search-close aria-label="Close search">
                <x-icon name="arrow-left" />
            </button>
            <label class="relative flex-1">
                <span class="sr-only">Search products</span>
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" autocomplete="off" enterkeyhint="search" spellcheck="false"
                       placeholder="Search atta, doodh, ice cream…" class="input rounded-2xl border-gray-100 bg-gray-50 py-3 pl-11 text-base focus:bg-white"
                       role="combobox" aria-expanded="false" aria-controls="search-results" aria-autocomplete="list" data-search-input>
            </label>
        </form>
        <div id="search-results" class="flex-1 overflow-y-auto overscroll-contain p-3" role="listbox" data-search-results></div>
    </div>
</div>

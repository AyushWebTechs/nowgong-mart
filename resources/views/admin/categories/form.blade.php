@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'New category')

@section('content')
    <a href="{{ route('admin.categories.index') }}" class="text-sm text-brand-700 hover:underline">← Categories</a>
    <h1 class="mt-2 mb-4 text-xl font-bold">{{ $category->exists ? "Edit {$category->name}" : 'New category' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="card grid max-w-2xl gap-4 p-5 sm:grid-cols-2">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div><label class="label" for="name">Name</label><input class="input" id="name" name="name" value="{{ old('name', $category->name) }}" required></div>
        <div><label class="label" for="slug">URL slug (auto if blank)</label><input class="input" id="slug" name="slug" value="{{ old('slug', $category->slug) }}"></div>
        <div>
            <label class="label" for="parent_id">Parent category</label>
            <select class="input" id="parent_id" name="parent_id">
                <option value="">None (top level)</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label" for="sort_order">Sort order</label><input class="input" id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="sm:col-span-2"><label class="label" for="description">Description</label><textarea class="input" id="description" name="description" rows="2">{{ old('description', $category->description) }}</textarea></div>
        <div>
            <label class="label" for="image">Image (JPG/PNG/WebP, max 2 MB)</label>
            <input class="input" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            @if ($category->image_path)
                <img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="mt-2 h-16 w-16 rounded object-cover">
            @endif
        </div>
        <label class="flex items-center gap-2 self-end text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Visible in the shop
        </label>
        <div class="sm:col-span-2"><button class="btn btn-primary">Save category</button></div>
    </form>
@endsection

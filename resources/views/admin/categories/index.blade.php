@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Categories</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">+ New category</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Name</th><th>Parent</th><th>Slug</th><th>Order</th><th>Products</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="font-semibold">{{ $category->name }}</td>
                        <td>{{ $category->parent?->name ?? '—' }}</td>
                        <td class="text-xs text-gray-500">{{ $category->slug }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td><span class="badge {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline" data-confirm="Delete {{ $category->name }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm text-rose-600 hover:bg-rose-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-500">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

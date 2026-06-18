<x-layouts.app>
    <x-slot:title>Edit {{ $category->name }}</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <nav class="text-sm text-neutral-500 mb-6">
            <a href="{{ route('admin.categories.index') }}" class="hover:text-neutral-700">Categories</a>
            <span class="mx-2">/</span>
            <span class="text-neutral-900">{{ $category->name }}</span>
        </nav>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Edit Category</h1>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                @include('admin.categories._form', compact('category', 'parentOptions'))

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('admin.categories.index') }}"
                       class="text-sm text-neutral-500 hover:text-neutral-700 transition-colors">Cancel</a>
                    <button type="submit"
                            class="bg-[#1A1A24] hover:bg-[#080810] text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                        Save changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-layouts.app>

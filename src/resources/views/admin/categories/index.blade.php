<x-layouts.app>
    <x-slot:title>Categories</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Categories</h1>
                <p class="text-sm text-neutral-500 mt-1">Manage the product and vehicle category tree.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                + Add Category
            </a>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($categories->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <svg class="w-10 h-10 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-sm font-semibold text-neutral-700">No categories yet</h3>
                <p class="text-sm text-neutral-500 mt-1">Add your first product category to get started.</p>
                <a href="{{ route('admin.categories.create') }}"
                   class="mt-4 inline-block bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                    Create first category
                </a>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Category</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Sub-categories</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3 hidden md:table-cell">Commission Override</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3 hidden lg:table-cell">Sort</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($categories as $category)
                            {{-- Root category row --}}
                            <tr class="hover:bg-neutral-50 transition-colors bg-white">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($category->icon)
                                            <span class="text-base">{{ $category->icon }}</span>
                                        @endif
                                        <span class="font-semibold text-neutral-900">{{ $category->name }}</span>
                                    </div>
                                    <div class="text-xs text-neutral-400 mt-0.5 font-mono">{{ $category->slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-neutral-500 hidden sm:table-cell">
                                    {{ $category->children->count() }}
                                </td>
                                <td class="px-4 py-3 text-center hidden md:table-cell">
                                    @if ($category->commission_override !== null)
                                        <span class="text-[#2EBD7A] font-medium">{{ $category->commission_override }}%</span>
                                    @else
                                        <span class="text-neutral-400 text-xs">default</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-neutral-500 hidden lg:table-cell tabular-nums">
                                    {{ $category->sort_order }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                           class="text-sm text-[#3DB8E8] hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                              onsubmit="return confirm('Delete {{ $category->name }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="text-sm text-red-500 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Child category rows --}}
                            @foreach ($category->children as $child)
                                <tr class="hover:bg-neutral-50 transition-colors bg-neutral-50/30">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2 pl-6">
                                            <span class="text-neutral-300">└</span>
                                            @if ($child->icon)
                                                <span class="text-sm">{{ $child->icon }}</span>
                                            @endif
                                            <span class="text-neutral-700">{{ $child->name }}</span>
                                        </div>
                                        <div class="text-xs text-neutral-400 pl-12 font-mono">{{ $child->slug }}</div>
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-neutral-400 hidden sm:table-cell">—</td>
                                    <td class="px-4 py-2.5 text-center hidden md:table-cell">
                                        @if ($child->commission_override !== null)
                                            <span class="text-[#2EBD7A] font-medium">{{ $child->commission_override }}%</span>
                                        @else
                                            <span class="text-neutral-400 text-xs">default</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-neutral-500 hidden lg:table-cell tabular-nums">{{ $child->sort_order }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.categories.edit', $child) }}"
                                               class="text-sm text-[#3DB8E8] hover:underline">Edit</a>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $child) }}"
                                                  onsubmit="return confirm('Delete {{ $child->name }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</x-layouts.app>

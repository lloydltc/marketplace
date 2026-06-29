<x-layouts.app>
    <x-slot:title>New Part</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0 max-w-2xl">
            <a href="{{ route('admin.parts.index') }}" class="text-body-sm text-muted hover:text-ink">← Parts catalog</a>
            <h1 class="text-h1 text-ink mt-2 mb-6">New canonical part</h1>

            <form method="POST" action="{{ route('admin.parts.store') }}" class="bg-surface border border-line rounded-xl shadow-e1 p-6 space-y-4">
                @csrf
                @include('admin.parts._form-fields', ['part' => null])
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.parts.index') }}" class="text-body-sm text-muted hover:text-ink px-4 py-2">Cancel</a>
                    <x-button type="submit" variant="primary">Create part</x-button>
                </div>
            </form>
        </div>
      </div>
    </div>
</x-layouts.app>

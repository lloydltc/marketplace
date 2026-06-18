<x-layouts.app>
    <x-slot:title>Edit Profile</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <a href="{{ route('vendor.profile.show') }}" class="text-sm text-[#3DB8E8] hover:underline">← Back to profile</a>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Edit Vendor Profile</h1>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('vendor.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @foreach ([
                    ['name', 'Business Name', 'text', $vendor->name],
                    ['contact_email', 'Contact Email', 'email', $vendor->contact_email],
                    ['phone', 'Phone Number', 'text', $vendor->phone],
                    ['address', 'Address', 'text', $vendor->address],
                    ['business_registration', 'Business Registration Number', 'text', $vendor->business_registration],
                    ['tax_id', 'Tax ID', 'text', $vendor->tax_id],
                ] as [$field, $label, $type, $value])
                    <div class="space-y-1">
                        <label for="{{ $field }}" class="block text-sm font-medium text-neutral-700">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                               value="{{ old($field, $value) }}"
                               class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                      @error($field) border-red-500 @else border-neutral-300 @enderror">
                        @error($field)
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="space-y-1">
                    <label for="description" class="block text-sm font-medium text-neutral-700">Description <span class="text-neutral-400">(optional)</span></label>
                    <textarea id="description" name="description" rows="3"
                              class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">{{ old('description', $vendor->description) }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('vendor.profile.show') }}"
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

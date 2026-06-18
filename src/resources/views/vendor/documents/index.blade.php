<x-layouts.app>
    <x-slot:title>Documents</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-2xl font-semibold text-neutral-900 mb-2">Verification Documents</h1>
        <p class="text-sm text-neutral-500 mb-6">
            Upload your business documents to speed up account approval. Accepted formats: PDF, JPG, PNG (max 5MB each).
        </p>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Uploaded documents --}}
        @if ($vendor->documents->isNotEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                @foreach ($vendor->documents as $doc)
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-neutral-100 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-neutral-900">{{ $doc->labelForType() }}</div>
                            <div class="text-xs text-neutral-400">{{ $doc->original_filename }}</div>
                            @if ($doc->isRejected() && $doc->rejection_reason)
                                <div class="text-xs text-red-600 mt-0.5">Reason: {{ $doc->rejection_reason }}</div>
                            @endif
                        </div>
                        @php
                            $docBadge = match($doc->status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default    => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium {{ $docBadge }}">{{ ucfirst($doc->status) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Upload form --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-neutral-900 mb-5">Upload Document</h2>
            <form method="POST" action="{{ route('vendor.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-1">
                    <label for="document_type" class="block text-sm font-medium text-neutral-700">Document Type</label>
                    <select id="document_type" name="document_type" required
                            class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
                        <option value="">Select type…</option>
                        @foreach ([
                            'business_registration' => 'Business Registration Certificate',
                            'tax_id'                => 'Tax ID Certificate',
                            'bank_proof'            => 'Bank Account Proof',
                            'id_copy'               => 'Director ID Copy',
                            'address_proof'         => 'Proof of Address',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label for="document" class="block text-sm font-medium text-neutral-700">File</label>
                    <input id="document" name="document" type="file"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="block w-full text-sm text-neutral-600
                                  file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:text-sm file:font-medium file:bg-[#F0A820]/10 file:text-[#1A1A24]
                                  hover:file:bg-[#F0A820]/20 cursor-pointer
                                  @error('document') border border-red-500 rounded-lg p-2 @enderror">
                    @error('document')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                    Upload document
                </button>
            </form>
        </div>

    </div>
</x-layouts.app>

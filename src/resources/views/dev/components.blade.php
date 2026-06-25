<x-layouts.app>
    <x-slot:title>Component gallery</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">
        <header class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-h1 text-[rgb(var(--text-strong))]">Component gallery</h1>
                <p class="text-body-sm text-[rgb(var(--text-muted))]">Salma Drive design system — toggle the theme in the nav to check both modes.</p>
            </div>
        </header>

        {{-- Buttons --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Buttons</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-button>Primary</x-button>
                <x-button variant="secondary">Secondary</x-button>
                <x-button variant="outline">Outline</x-button>
                <x-button variant="ghost">Ghost</x-button>
                <x-button variant="danger">Delete</x-button>
                <x-button variant="whatsapp">WhatsApp</x-button>
                <x-button :loading="true">Saving</x-button>
                <x-button disabled>Disabled</x-button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-button size="sm">Small</x-button>
                <x-button size="md">Medium</x-button>
                <x-button size="lg">Large</x-button>
                <x-button href="#">Link button</x-button>
            </div>
        </section>

        {{-- Badges --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Badges</h2>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge variant="featured" />
                <x-badge variant="verified" />
                <x-badge variant="unverified" />
                <x-badge variant="new" />
                <x-badge variant="used" />
                <x-badge variant="sold" />
                <x-badge variant="recent-import" />
                <x-badge variant="duty-paid" />
                <x-badge variant="poa" />
            </div>
        </section>

        {{-- Form controls --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Form controls</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <x-input label="Full name" name="demo_name" placeholder="Tendai Moyo" hint="As it appears on your ID." />
                <x-input label="Price (USD)" name="demo_price" type="number" error="Enter a valid amount." />
                <x-select label="Transmission" name="demo_trans">
                    <option value="">Select…</option>
                    <option>Automatic</option>
                    <option>Manual</option>
                </x-select>
                <x-textarea label="Description" name="demo_desc" hint="Keep it factual." />
            </div>
            <div class="flex flex-wrap items-center gap-6">
                <x-checkbox name="demo_dp" label="Duty paid" />
                <x-radio name="demo_cond" value="used" label="Used" />
                <x-radio name="demo_cond" value="new" label="New" />
                <x-toggle name="demo_show_price" label="Show price" :checked="true" />
            </div>
        </section>

        {{-- Cards + stat tiles --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Cards &amp; stat tiles</h2>
            <div class="grid sm:grid-cols-3 gap-5">
                <x-card>
                    <h3 class="text-h4 text-[rgb(var(--text-strong))]">Resting card</h3>
                    <p class="mt-1 text-body-sm text-[rgb(var(--text-muted))]">Surface, hairline border, e1 shadow.</p>
                </x-card>
                <x-stat-tile label="Profile views" value="12,480" :delta="12" :arc="0.72" caption="Last 30 days" />
                <x-stat-tile label="WhatsApp clicks" value="318" :delta="-4" :arc="0.34" caption="Last 30 days" />
            </div>
        </section>

        {{-- Tabs --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Tabs</h2>
            <x-tabs :tabs="['cars' => 'Cars', 'bikes' => 'Bikes', 'boats' => 'Boats']" default="cars">
                <div x-show="tab === 'cars'" role="tabpanel" class="text-body-sm text-[rgb(var(--text))]">Cars panel.</div>
                <div x-show="tab === 'bikes'" role="tabpanel" class="text-body-sm text-[rgb(var(--text))]" x-cloak>Bikes panel.</div>
                <div x-show="tab === 'boats'" role="tabpanel" class="text-body-sm text-[rgb(var(--text))]" x-cloak>Boats panel.</div>
            </x-tabs>
        </section>

        {{-- Overlays + feedback --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Overlays &amp; feedback</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-button variant="outline" x-data @click="$dispatch('open-modal', 'demo')">Open modal</x-button>
                <x-button variant="outline" x-data @click="$dispatch('open-drawer', 'demo')">Open drawer</x-button>
                <x-button variant="outline" x-data @click="$dispatch('toast', { type: 'success', message: 'Published' })">Show toast</x-button>
                <x-tooltip text="Masked until you tap reveal.">
                    <span class="text-body-sm text-[rgb(var(--info))] underline decoration-dotted cursor-help">Hover me</span>
                </x-tooltip>
            </div>

            <x-modal name="demo" title="Confirm publish">
                <p class="text-body-sm">Your listing will go live and appear in search results.</p>
                <x-slot:actions>
                    <x-button variant="ghost" x-data @click="$dispatch('close-modal', 'demo')">Cancel</x-button>
                    <x-button x-data @click="$dispatch('close-modal', 'demo'); $dispatch('toast', { type: 'success', message: 'Published' })">Publish</x-button>
                </x-slot:actions>
            </x-modal>

            <x-drawer name="demo" side="right" title="Filters">
                <p class="text-body-sm text-[rgb(var(--text-muted))]">Filter groups go here.</p>
            </x-drawer>
        </section>

        {{-- Navigation + data --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Navigation &amp; data</h2>
            <x-breadcrumbs :items="[
                ['label' => 'Vehicles', 'url' => '#'],
                ['label' => 'Toyota', 'url' => '#'],
                ['label' => 'Hilux 2.8 GD-6'],
            ]" />

            <x-table>
                <x-slot:head>
                    <th>Listing</th>
                    <th>Status</th>
                    <th class="!text-right">Price</th>
                </x-slot:head>
                <tr>
                    <td>2022 Toyota Hilux</td>
                    <td><x-badge variant="verified" /></td>
                    <td class="text-right tabular-nums">$45,500</td>
                </tr>
                <tr>
                    <td>2016 VW Golf R</td>
                    <td><x-badge variant="sold" /></td>
                    <td class="text-right tabular-nums">$28,000</td>
                </tr>
            </x-table>

            <x-pagination :paginator="$paginator" />
        </section>

        {{-- Loading + empty --}}
        <section class="space-y-4">
            <h2 class="text-h3 text-[rgb(var(--text-strong))]">Loading &amp; empty</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <x-card>
                    <x-skeleton class="h-32 w-full" />
                    <x-skeleton class="h-4 w-3/4 mt-4" />
                    <x-skeleton class="h-4 w-1/2 mt-2" />
                </x-card>
                <x-empty title="No listings yet" message="Add your first vehicle to start receiving enquiries.">
                    <x-button>Add a vehicle</x-button>
                </x-empty>
            </div>
        </section>
    </div>
</x-layouts.app>

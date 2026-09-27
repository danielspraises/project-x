@props(['name', 'options', 'selected' => null, 'placeholder' => 'Select...', 'required' => false, 'multiple' => false])

@if ($multiple)
    <div
        x-data="{
            open: false,
            search: '',
            selected: @js(collect($selected ?? [])->map(fn ($v) => (string) $v)->values()->all()),
            options: @js($options),
            get filtered() {
                if (!this.search) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter(o => o.label.toLowerCase().includes(q));
            },
            get selectedLabels() {
                return this.selected
                    .map(v => (this.options.find(o => String(o.value) === v) || {}).label)
                    .filter(Boolean);
            },
            isSelected(option) {
                return this.selected.includes(String(option.value));
            },
            toggle(option) {
                const value = String(option.value);
                const idx = this.selected.indexOf(value);
                if (idx === -1) {
                    this.selected.push(value);
                } else {
                    this.selected.splice(idx, 1);
                }
            },
            remove(value) {
                this.selected = this.selected.filter(v => v !== value);
            },
        }"
        class="relative"
        @click.outside="open = false"
        @keydown.escape="open = false"
    >
        <template x-for="value in selected" :key="value">
            <input type="hidden" :name="'{{ $name }}[]'" :value="value">
        </template>

        <button type="button" @click="open = !open"
                class="w-full text-left rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm bg-white flex justify-between items-center gap-2 hover:border-gray-400 transition">
            <span class="truncate" :class="{ 'text-gray-400': selected.length === 0 }"
                  x-text="selected.length ? selected.length + ' selected' : '{{ $placeholder }}'"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="selected.length > 0" class="mt-2 flex flex-wrap gap-1.5">
            <template x-for="value in selected" :key="'chip-'+value">
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">
                    <span x-text="(options.find(o => String(o.value) === value) || {}).label"></span>
                    <button type="button" @click="remove(value)" class="text-gray-400 hover:text-gray-700">&times;</button>
                </span>
            </template>
        </div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-64 overflow-hidden flex flex-col"
            style="display: none;"
        >
            <div class="p-2 border-b shrink-0">
                <input type="text" x-model="search" x-ref="searchInput" placeholder="Type to search..."
                       class="w-full text-sm rounded-md border-gray-300 focus:border-gray-400 focus:ring-0" @click.stop>
            </div>
            <div class="overflow-y-auto">
                <template x-for="option in filtered" :key="option.value">
                    <button type="button" @click="toggle(option)"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                            :class="{ 'bg-gray-100 font-medium': isSelected(option) }">
                        <span class="w-4 h-4 shrink-0 rounded border border-gray-300 flex items-center justify-center"
                              :class="{ 'bg-brand border-brand': isSelected(option) }">
                            <svg x-show="isSelected(option)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span x-text="option.label"></span>
                    </button>
                </template>
                <template x-if="filtered.length === 0">
                    <p class="px-3 py-2 text-sm text-gray-400">No matches</p>
                </template>
            </div>
        </div>
    </div>
@else
    <div
        x-data="{
            open: false,
            search: '',
            selected: @js((string) $selected),
            options: @js($options),
            get filtered() {
                if (!this.search) return this.options;
                const q = this.search.toLowerCase();
                return this.options.filter(o => o.label.toLowerCase().includes(q));
            },
            get selectedLabel() {
                const found = this.options.find(o => String(o.value) === this.selected);
                return found ? found.label : '';
            },
            select(option) {
                this.selected = String(option.value);
                this.search = '';
                this.open = false;
            },
            clear() {
                this.selected = '';
                this.open = false;
            },
        }"
        class="relative"
        @click.outside="open = false"
        @keydown.escape="open = false"
    >
        <input type="hidden" name="{{ $name }}" :value="selected" {{ $required ? 'required' : '' }}>

        <button type="button" @click="open = !open"
                class="w-full text-left rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm bg-white flex justify-between items-center gap-2 hover:border-gray-400 transition">
            <span x-text="selectedLabel || '{{ $placeholder }}'" :class="{ 'text-gray-400': !selectedLabel }" class="truncate"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-64 overflow-hidden flex flex-col"
            style="display: none;"
        >
            <div class="p-2 border-b shrink-0">
                <input type="text" x-model="search" x-ref="searchInput" placeholder="Type to search..."
                       class="w-full text-sm rounded-md border-gray-300 focus:border-gray-400 focus:ring-0" @click.stop>
            </div>
            <div class="overflow-y-auto">
                @if (! $required)
                    <button type="button" @click="clear()" class="w-full text-left px-3 py-2 text-sm text-gray-400 hover:bg-gray-50 italic">
                        {{ $placeholder }}
                    </button>
                @endif
                <template x-for="option in filtered" :key="option.value">
                    <button type="button" @click="select(option)"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                            :class="{ 'bg-gray-100 font-medium': String(option.value) === selected }"
                            x-text="option.label">
                    </button>
                </template>
                <template x-if="filtered.length === 0">
                    <p class="px-3 py-2 text-sm text-gray-400">No matches</p>
                </template>
            </div>
        </div>
    </div>
@endif

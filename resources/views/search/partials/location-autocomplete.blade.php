@php
    $value ??= '';
    $placeholder ??= 'e.g. London, SW1A';
@endphp

<div
    x-data="locationAutocomplete('{{ str_replace("'", "\\'", $value) }}')"
    x-on:click.outside="open = false"
    class="relative flex-1"
>
    <input
        type="text"
        name="location"
        placeholder="{{ $placeholder }}"
        x-model="query"
        x-ref="locationInput"
        x-on:input.debounce.200ms="fetchSuggestions()"
        x-on:focus="query.length >= 2 && suggestions.length && (open = true)"
        x-on:keydown.arrow-down.prevent="moveDown()"
        x-on:keydown.arrow-up.prevent="moveUp()"
        x-on:keydown.enter="selectHighlighted($event)"
        x-on:keydown.escape="open = false"
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="open"
        aria-controls="location-listbox"
        :aria-activedescendant="highlightedIndex >= 0 ? 'suggestion-' + highlightedIndex : null"
        class="w-full rounded-lg border-2 px-4 py-3 text-gray-900 focus:border-gray-900 focus:outline-none"
        :class="validationFailed ? 'border-red-500' : 'border-gray-300'"
    >

    {{-- Validation error message (driven by parent form) --}}
    <p
        x-show="validationFailed"
        x-cloak
        class="mt-1 text-left text-sm text-red-600"
    >
        Please enter a location.
    </p>

    {{-- Dropdown --}}
    <ul
        x-show="open && suggestions.length > 0"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        id="location-listbox"
        role="listbox"
        class="absolute z-50 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.slug">
            <li
                :id="'suggestion-' + index"
                role="option"
                :aria-selected="index === highlightedIndex"
                x-on:click="select(suggestion)"
                x-on:mouseover="highlightedIndex = index"
                class="cursor-pointer px-4 py-2.5 text-sm text-gray-900"
                :class="index === highlightedIndex ? 'bg-gray-100' : ''"
                x-text="suggestion.name"
            ></li>
        </template>
    </ul>
</div>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('locationAutocomplete', (initialValue = '') => ({
            query: initialValue,
            suggestions: [],
            highlightedIndex: -1,
            open: false,
            loading: false,
            validationFailed: false,
            abortController: null,

            fetchSuggestions() {
                const q = this.query.trim();

                if (q.length < 2) {
                    this.suggestions = [];
                    this.open = false;
                    return;
                }

                if (this.abortController) {
                    this.abortController.abort();
                }
                this.abortController = new AbortController();
                this.loading = true;

                fetch(`/api/search-suggest?q=${encodeURIComponent(q)}`, {
                    signal: this.abortController.signal,
                })
                    .then(r => r.json())
                    .then(data => {
                        this.suggestions = data;
                        this.highlightedIndex = -1;
                        this.open = data.length > 0;
                        this.loading = false;
                    })
                    .catch(e => {
                        if (e.name !== 'AbortError') {
                            this.loading = false;
                        }
                    });
            },

            select(suggestion) {
                this.query = suggestion.name;
                this.open = false;
                this.validationFailed = false;
            },

            moveDown() {
                if (!this.open) return;
                this.highlightedIndex = (this.highlightedIndex + 1) % this.suggestions.length;
            },

            moveUp() {
                if (!this.open) return;
                this.highlightedIndex = this.highlightedIndex <= 0
                    ? this.suggestions.length - 1
                    : this.highlightedIndex - 1;
            },

            selectHighlighted(event) {
                if (this.open && this.highlightedIndex >= 0) {
                    event.preventDefault();
                    this.select(this.suggestions[this.highlightedIndex]);
                }
            },
        }));
    });
</script>
@endonce

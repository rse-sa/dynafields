<div
    x-data="{
        slots: [0],
        max: {{ (int) $maxFiles }},
        add() {
            if (this.slots.length >= this.max) return;
            this.slots.push(this.slots.length);
        }
    }"
    class="space-y-2"
>
    <template x-for="(slot, index) in slots" :key="slot">
        <input
            type="file"
            :name="'{{ $inputName }}[' + index + ']'"
            @if($isMandatory && ! $hasExisting) :required="index === 0" @endif
            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
        />
    </template>

    @if($maxFiles > 1)
        <button
            type="button"
            x-on:click="add()"
            x-bind:disabled="slots.length >= max"
            class="text-xs text-indigo-600 hover:underline disabled:opacity-40 disabled:cursor-not-allowed"
        >
            + {{ __('dynafields::dynafields.add_file') }}
        </button>
        <p class="text-xs text-gray-400">
            {{ __('dynafields::dynafields.max_files', ['count' => $maxFiles]) }}
        </p>
    @endif
</div>

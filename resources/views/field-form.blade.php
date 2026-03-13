{{--
  Publishable admin view for creating/editing field definitions.
  Run: php artisan vendor:publish --tag=dynafields-views
  then customize resources/views/vendor/dynafields/field-form.blade.php
--}}
<div>
    <form method="POST" action="{{ $form_action }}">
        @csrf
        @if($action === 'edit') @method('PUT') @endif

        <input type="hidden" name="owner_type" value="{{ $r->owner_type ?? request('owner_type') }}"/>
        <input type="hidden" name="owner_id" value="{{ $r->owner_id ?? request('owner_id') }}"/>

        <div
            x-data="{
                type: '{{ $r->type ?? 'text' }}',
                options: @json($r->data['options'] ?? []),
                addOption() { this.options.push(''); },
                removeOption(i) { this.options.splice(i, 1); }
            }"
        >
            {{-- Translatable name --}}
            @foreach(config('app.supported_locales', ['en']) as $locale)
                <div>
                    <label>{{ __('dynafields::dynafields.field_name') }} ({{ strtoupper($locale) }})</label>
                    <input type="text" name="name[{{ $locale }}]"
                           value="{{ $r->getTranslation('name', $locale, false) ?? '' }}"
                           required/>
                    @error("name.{$locale}") <span>{{ $message }}</span> @enderror
                </div>
                <div>
                    <label>{{ __('dynafields::dynafields.field_description') }} ({{ strtoupper($locale) }})</label>
                    <input type="text" name="description[{{ $locale }}]"
                           value="{{ $r->getTranslation('description', $locale, false) ?? '' }}"/>
                </div>
            @endforeach

            {{-- Type --}}
            <div>
                <label>{{ __('dynafields::dynafields.field_type') }}</label>
                <select name="type" x-model="type">
                    @foreach(config('dynafields.field_types') as $type)
                        <option value="{{ $type }}" @selected($r->type === $type)>
                            {{ __("dynafields::dynafields.field_type_{$type}") }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Options (for select) --}}
            <div x-show="type === 'select'">
                <label>{{ __('dynafields::dynafields.field_options') }}</label>
                <template x-for="(option, i) in options" :key="i">
                    <div>
                        <input type="text" name="options[]" x-model="options[i]"/>
                        <button type="button" x-on:click="removeOption(i)">✕</button>
                    </div>
                </template>
                <button type="button" x-on:click="addOption">+ {{ __('dynafields::dynafields.add_option') }}</button>
            </div>

            {{-- Default value (text/textarea) --}}
            <div x-show="type === 'text' || type === 'textarea'">
                <label>{{ __('dynafields::dynafields.field_default_value') }}</label>
                <input type="text" name="default_value_text" value="{{ $r->default_value ?? '' }}"/>
            </div>

            {{-- Default value (boolean) --}}
            <div x-show="type === 'boolean'">
                <label>{{ __('dynafields::dynafields.field_default_value') }}</label>
                <select name="default_value_boolean">
                    <option value="1" @selected($r->default_value == '1')>{{ __('dynafields::dynafields.yes') }}</option>
                    <option value="0" @selected($r->default_value == '0')>{{ __('dynafields::dynafields.no') }}</option>
                </select>
            </div>

            {{-- Order --}}
            <div>
                <label>{{ __('dynafields::dynafields.field_order') }}</label>
                <input type="number" name="order" value="{{ $r->order ?? '' }}"/>
            </div>

            {{-- Max chars (text/textarea) --}}
            <div x-show="type === 'text' || type === 'textarea'">
                <label>{{ __('dynafields::dynafields.field_max_chars') }}</label>
                <input type="number" name="max_chars" value="{{ $r->max_chars ?? '' }}"/>
            </div>

            {{-- Booleans --}}
            <div>
                <label>
                    <input type="hidden" name="is_mandatory" value="0"/>
                    <input type="checkbox" name="is_mandatory" value="1" @checked($r->is_mandatory ?? false)/>
                    {{ __('dynafields::dynafields.field_is_mandatory') }}
                </label>
            </div>
            <div>
                <label>
                    <input type="hidden" name="is_unique" value="0"/>
                    <input type="checkbox" name="is_unique" value="1" @checked($r->is_unique ?? false)/>
                    {{ __('dynafields::dynafields.field_is_unique') }}
                </label>
            </div>
            <div>
                <label>
                    <input type="hidden" name="is_printable" value="0"/>
                    <input type="checkbox" name="is_printable" value="1" @checked($r->is_printable ?? true)/>
                    {{ __('dynafields::dynafields.field_is_printable') }}
                </label>
            </div>
            <div>
                <label>
                    <input type="hidden" name="is_fixed" value="0"/>
                    <input type="checkbox" name="is_fixed" value="1" @checked($r->is_fixed ?? false)/>
                    {{ __('dynafields::dynafields.field_is_fixed') }}
                </label>
            </div>

            <div>
                <button type="submit">{{ __('dynafields::dynafields.save') }}</button>
                <a href="{{ $back_to }}">{{ __('dynafields::dynafields.cancel') }}</a>
            </div>
        </div>
    </form>
</div>

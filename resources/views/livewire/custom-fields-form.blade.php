<div>
    @if($fields->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2">
            @foreach($fields as $field)
                @php
                    $currentValue = ($action === 'create')
                        ? ($field->default_value ?? '')
                        : ($valueArray[$field->getKey()] ?? '');
                    $isDisabled   = ($field->is_fixed && $action !== 'create') || $field->is_readonly;
                    $inputName    = "fields[{$field->getKey()}]";
                    $fieldLabel   = is_array($field->name)
                        ? ($field->name[app()->getLocale()] ?? reset($field->name))
                        : $field->name;
                @endphp

                <div class="relative mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $fieldLabel }}
                        @if($field->is_mandatory)<span class="text-red-500">*</span>@endif
                    </label>

                    @if($field->type === 'select')
                        <select
                            name="{{ $inputName }}"
                            @if($isDisabled) disabled @endif
                            @if($field->is_mandatory) required @endif
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        >
                            <option value=""></option>
                            @foreach($field->data['options'] ?? [] as $option)
                                <option value="{{ $option }}" @selected($currentValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>

                    @elseif($field->type === 'boolean' || $field->type === 'checkbox')
                        <input
                            type="checkbox"
                            name="{{ $inputName }}"
                            value="1"
                            @if($currentValue) checked @endif
                            @if($isDisabled) disabled @endif
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />

                    @elseif($field->type === 'textarea')
                        <textarea
                            name="{{ $inputName }}"
                            @if($isDisabled) disabled @endif
                            @if($field->is_mandatory) required @endif
                            @if($field->max_chars) maxlength="{{ $field->max_chars }}" @endif
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            rows="3"
                        >{{ $currentValue }}</textarea>

                    @elseif($field->type === 'file')
                        @php $fileValue = $fileValues[$field->getKey()] ?? null; @endphp
                        @if($fileValue !== null)
                            @if(config('dynafields.file_preview_view'))
                                @include(config('dynafields.file_preview_view'), [
                                    'field'        => $field,
                                    'fileValue'    => $fileValue,
                                    'storedValue'  => $currentValue,
                                    'isDisabled'   => $isDisabled,
                                ])
                            @else
                                @include('dynafields::livewire.partials.file-preview', [
                                    'fileValue'  => $fileValue,
                                    'isDisabled' => $isDisabled,
                                ])
                            @endif
                        @endif
                        @if(! $isDisabled)
                            @if($field->allowsMultipleFiles())
                                @include('dynafields::livewire.partials.file-multi-input', [
                                    'inputName'   => $inputName,
                                    'maxFiles'    => $field->maxFiles(),
                                    'isMandatory' => $field->is_mandatory,
                                    'hasExisting' => (bool) $currentValue,
                                ])
                            @else
                                <input
                                    type="file"
                                    name="{{ $inputName }}"
                                    @if($field->is_mandatory && ! $currentValue) required @endif
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                            @endif
                        @endif

                    @else
                        <input
                            type="{{ $field->type }}"
                            name="{{ $inputName }}"
                            value="{{ $currentValue }}"
                            @if($isDisabled) disabled @endif
                            @if($field->is_mandatory) required @endif
                            @if($field->max_chars) maxlength="{{ $field->max_chars }}" @endif
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    @endif

                    {{-- Scope indicators --}}
                    @if($field->is_global || $field->is_inherited || $field->is_readonly)
                        <div class="absolute top-0 end-0 flex gap-1 text-xs opacity-50 mt-1">
                            @if($field->is_global)
                                <span title="{{ __('dynafields::dynafields.global_field') }}">🌍</span>
                            @elseif($field->is_inherited)
                                <span title="{{ __('dynafields::dynafields.inherited_field') }}">↓</span>
                            @endif
                            @if($field->is_readonly)
                                <span title="{{ __('dynafields::dynafields.field_is_unchangeable') }}">🔒</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@php
    use RSE\DynaFields\DTOs\FileFieldValue;
    $name = $fileValue instanceof FileFieldValue ? $fileValue->name         : (string) $fileValue;
    $size = $fileValue instanceof FileFieldValue ? $fileValue->formattedSize() : null;
    $date = $fileValue instanceof FileFieldValue && $fileValue->date ? $fileValue->date->format('Y-m-d') : null;
    $href = $fileValue instanceof FileFieldValue ? $fileValue->downloadLink : null;
@endphp

<div dir="ltr" class="bg-white border border-slate-200 rounded-xl mb-2">
    <div class="flex items-center gap-4 p-4">

        <div class="shrink-0 w-10 h-10 flex items-center justify-center bg-slate-50 rounded-lg">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
        </div>

        <div class="flex-1 min-w-0">
            @if($href)
                <a href="{{ $href }}" target="_blank"
                   class="block font-medium text-slate-900 hover:text-blue-600 transition-colors truncate">
                    {{ $name }}
                </a>
            @else
                <span class="block font-medium text-slate-900 truncate">{{ $name }}</span>
            @endif

            @if($size || $date)
                <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                    @if($size)<span>{{ $size }}</span>@endif
                    @if($size && $date)<span class="text-slate-400">·</span>@endif
                    @if($date)<span>{{ $date }}</span>@endif
                </div>
            @endif
        </div>

        @if($href)
            <div class="shrink-0">
                <a href="{{ $href }}" target="_blank"
                   class="flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all"
                   title="{{ app()->isLocale('ar') ? 'تحميل' : 'Download' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </a>
            </div>
        @endif

    </div>
</div>

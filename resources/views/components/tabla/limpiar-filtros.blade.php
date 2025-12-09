@props([
    'href' => '#',
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'flex items-center gap-1 text-xs font-semibold text-yellow-900 bg-yellow-200 hover:bg-yellow-300 px-1.5 py-1.5 rounded-lg shadow-sm transition focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900',
    ]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
    </svg>
</a>

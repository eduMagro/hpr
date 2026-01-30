@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'breadcrumb' => null,
])

<div 
    x-data="{ 
        scrolled: false,
        init() {
            // Buscamos el elemento main que es el que tiene el scroll
            let main = document.querySelector('main');
            // Si no hay main, usamos window por fallback (aunque el layout usa main)
            let scrollTarget = main || window;
            
            const onScroll = () => {
                const scrollTop = main ? main.scrollTop : window.pageYOffset;
                this.scrolled = scrollTop > 10;
            };

            scrollTarget.addEventListener('scroll', onScroll, { passive: true });
            
            // Verificamos estado inicial
            onScroll();
        }
    }" 
    class="sticky top-0 z-40 flex flex-wrap items-center justify-between gap-4 p-4 transition-all duration-300 sm:px-6 -mx-4 sm:-mx-6 mb-6">
    <div class="relative flex items-center gap-3.5 p-4 rounded-2xl transition-all duration-500" :class="{ 'bg-white/30 dark:bg-gray-900/30 backdrop-blur-md shadow-sm left-1/2 -translate-x-1/2 py-2 px-3 -translate-y-2 scale-95 ease-[cubic-bezier(0.34,1.56,0.64,1)]': scrolled, 'bg-transparent left-0 translate-x-0 scale-100 ease-[cubic-bezier(0.34,1.1,0.64,1)]': !scrolled }">
        @if($icon)
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-[0_2px_8px_rgba(59,130,246,0.25)] transition-all sm:h-10 sm:w-10 dark:shadow-[0_2px_8px_rgba(59,130,246,0.4)]">
                <div class="h-[1.125rem] w-[1.125rem] sm:h-5 sm:w-5">
                    {!! $icon !!}
                </div>
            </div>
        @endif
        <div class="flex flex-col transition-all duration-300 group" :class="{ 'gap-0': scrolled, 'gap-0.5': !scrolled }">
            <h1 class="m-0 font-semibold leading-[1.3] tracking-[-0.01em] text-slate-800 transition-all duration-300 dark:text-slate-50"
                >
                {{ $title }}
            </h1>
            @if($subtitle)
                <p class="m-0 text-xs leading-[1.4] text-slate-800 transition-all duration-300 overflow-hidden whitespace-nowrap sm:text-[0.8125rem] dark:text-slate-400"
                   :class="{ 'max-h-0 opacity-0 max-w-0': scrolled, 'max-h-[50px] opacity-100 max-w-xs sm:max-w-md': !scrolled }">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>
    @if($slot->isNotEmpty())
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            {{ $slot }}
        </div>
    @endif
</div>


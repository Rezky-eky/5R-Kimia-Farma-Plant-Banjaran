<template>
    <button
        class="inline-flex min-w-[3rem] items-center justify-center rounded-xl border border-transparent px-5 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-200 ease-in-out hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70"
        style="background-color: #78a9c3; box-shadow: 0 10px 15px -3px rgba(65, 108, 132, 0.24), 0 4px 6px -2px rgba(65, 108, 132, 0.16);"
        @mouseenter="$event.target.style.backgroundColor='#6696b2'; $event.target.style.boxShadow='0 18px 28px -8px rgba(65, 108, 132, 0.34)';"
        @mouseleave="$event.target.style.backgroundColor='#78a9c3'; $event.target.style.boxShadow='0 10px 15px -3px rgba(65, 108, 132, 0.24), 0 4px 6px -2px rgba(65, 108, 132, 0.16)';"
        @focus="$event.target.style.outline='2px solid #78a9c3'; $event.target.style.outlineOffset='2px';"
    >
        <svg
            v-if="$attrs.disabled || $attrs['aria-busy'] || $attrs.class?.includes('opacity-25')"
            class="mr-2 h-4 w-4 animate-spin text-white/90"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>

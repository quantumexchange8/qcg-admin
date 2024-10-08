export default {
    root: ({ props, state }) => ({
        class: [
            // Font
            'leading-none',

            // Display and Position
            'inline-flex',
            'relative',

            // Shape
            'rounded',

            // Color and Background
            { 'bg-white': !props.disabled },
            'border',
            { 'border-gray-300': !props.invalid },

            // Invalid State
            // 'invalid:focus:ring-red-200',
            // 'invalid:hover:border-red-500',
            { 'border-error-500 focus:border-error-500': props.invalid },

            // Transitions
            'transition-all',
            'duration-200',

            // States
            { 'hover:border-primary-500': !props.invalid },
            { 'outline-none outline-offset-0 ring-0 ring-primary-500 z-10': state.focused },
            { 'shadow-focus': !props.invalid && state.focused },
            { 'shadow-input': !props.invalid && !state.focused },
        ]
    }),
    labelContainer: 'relative overflow-hidden flex items-center flex-auto cursor-pointer rounded',
    label: ({ props }) => ({
        class: [
            'caret-primary-500 text-sm',

            // Spacing
            {
                'py-3 px-4': props.display === 'comma' || (props.display === 'chip' && !props?.modelValue?.length),
                'py-1 px-1': props.display === 'chip' && props?.modelValue?.length > 0,
                'py-2 px-3': props.display == null
            },

            // Color
            { 'text-gray-950': props.modelValue?.length, 'text-gray-400': !props.modelValue?.length },
            'placeholder:text-gray-500',

            // Transitions
            'transition duration-200',

            // Misc
            'overflow-hidden whitespace-nowrap cursor-pointer overflow-ellipsis'
        ]
    }),
    dropdown: {
        class: [
            // Flexbox
            'flex items-center justify-center',
            'shrink-0',

            // Color and Background
            'bg-transparent',
            'text-gray-500',

            // Size
            'w-8',

            // Shape
            'rounded'
        ]
    },
    overlay: {
        class: [
            // Colors
            'bg-white',
            'text-gray-700',

            // Shape
            'border border-gray-300',
            'rounded',
            'shadow-md',
            'mt-[2px]'
        ]
    },
    header: {
        class: [
            //Flex
            'flex items-center justify-between',

            // Spacing
            'py-3 px-4 gap-2',
            'm-0',

            //Shape
            'border-b-0',
            'rounded',

            // Color
            'text-gray-700',
            'bg-white',
            'border-gray-300',

            '[&_[data-pc-name=pcfilter]]:w-full'
        ]
    },
    pcHeaderCheckbox: {
        root: [
            'relative cursor-pointer select-none',
            'w-4',
            'h-4'
        ],
        icon: [
            'w-3 h-3',
            'text-white',
        ]
    },
    listContainer: {
        class: [
            // Sizing
            'max-h-[200px]',

            // Misc
            'overflow-auto'
        ]
    },
    list: {
        class: 'p-1 list-none m-0'
    },
    option: ({ context }) => ({
        class: [
            'relative',
            'flex items-center',

            // Font
            'leading-none',

            // Spacing
            'm-0 px-3 py-2 gap-2',
            'first:mt-0 mt-[2px]',

            // Shape
            'border-0 rounded',

            // Colors
            {
                'text-gray-700': !context.focused && !context.selected,
                'bg-gray-200': context.focused && !context.selected,
                'text-gray-700': context.focused && !context.selected,
                'bg-highlight': context.selected
            },

            //States
            { 'hover:bg-gray-100': !context.focused && !context.selected },
            { 'hover:bg-highlight-emphasis': context.selected },
            { 'hover:text-gray-700 hover:bg-gray-100': context.focused && !context.selected },

            // Transition
            'transition-shadow duration-200',

            // Misc
            'cursor-pointer overflow-hidden whitespace-nowrap'
        ]
    }),
    optionGroup: {
        class: [
            'font-semibold',

            // Spacing
            'm-0 py-2 px-3',

            // Colors
            'text-gray-400',

            // Misc
            'cursor-auto'
        ]
    },
    pcOptionCheckbox: {
        root: [
            'relative cursor-pointer select-none',
            'w-4',
            'h-4'
        ],
        icon: [
            'w-3 h-3',
            'text-white',
        ]
    },
    emptyMessage: {
        class: [
            // Font
            'leading-none',

            // Spacing
            'py-2 px-3',

            // Color
            'text-gray-800',
            'bg-transparent'
        ]
    },
    loadingIcon: {
        class: 'text-gray-400 animate-spin'
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};

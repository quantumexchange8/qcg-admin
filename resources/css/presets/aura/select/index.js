export default {
    root: ({ props, state, parent }) => ({
        class: [
            // Display and Position
            'inline-flex',
            'relative',
            { 'w-full': props.fluid },
            // Shape
            { 'rounded': parent.instance.$name !== 'InputGroup' },
            { 'first:rounded-l rounded-none last:rounded-r': parent.instance.$name == 'InputGroup' },
            { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
            { 'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons },

            // Color and Background
            { 'bg-white': !props.disabled },

            'border',
            { '': parent.instance.$name != 'InputGroup' },
            { '': parent.instance.$name == 'InputGroup' },
            { 'border-gray-300': !props.invalid },

            // Invalid State
            // 'invalid:focus:ring-error-500',
            'invalid:hover:border-error-500',
            { 'border-error-500 focus:border-error-500': props.invalid },

            // Transitions
            'transition-all',
            'duration-200',

            // States
            { 'hover:border-primary-500': !props.invalid },
            { 'outline-none outline-offset-0 ring-0 ring-primary-500 z-10': state.focused },
            { 'shadow-focus': !props.invalid && state.focused },
            { 'shadow-input': !props.invalid && !state.focused },

            // Misc
            'cursor-pointer',
            'select-none',
            { 'bg-gray-100 select-none pointer-events-none cursor-default': props.disabled },

        ]
    }),
    label: ({ props, parent }) => ({
        class: [
            //Font
            'text-sm',

            // Display
            'block',
            'flex-auto',

            // Color and Background
            'bg-transparent',
            'border-0',
            { 'text-gray-950': props.modelValue != undefined, 'text-gray-400': props.modelValue == undefined },
            'placeholder:text-gray-400',

            // Sizing and Spacing
            'w-[1%]',
            'py-3 px-4',
            { 'pr-7': props.showClear },

            //Shape
            'rounded-l',

            // Transitions
            'transition',
            'duration-200',

            // States
            'focus:outline-none focus:shadow-none',

            // Filled State *for FloatLabel
            { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null },

            // Misc
            'relative',
            'cursor-pointer',
            'overflow-hidden overflow-ellipsis',
            'whitespace-nowrap',
            'appearance-none'
        ]
    }),
    dropdown: ({ parent, state }) => ({
        class: [
            // Flexbox
            'flex items-center justify-center',
            'shrink-0',

            // Color and Background
            'bg-transparent',
            'text-gray-500',

            // Size
            'w-10',

            // Shape
            'rounded-r',
            { 'rotate-180': parent.instance.$name === 'InputGroup' || state.focused },
            { 'rotate-0':  !state.focused },
        ]
    }),
    overlay: {
        class: [
            // Colors
            'bg-white',
            'text-gray-950',

            // Shape
            'border border-gray-300',
            'rounded',
            'shadow-dropdown',

            'mt-1'
        ]
    },
    listContainer: {
        class: [
            // Sizing
            'max-h-[236px]',

            // Misc
            'overflow-auto'
        ]
    },
    list: {
        class: 'list-none py-2'
    },
    option: ({ context }) => ({
        class: [
            'relative',
            'flex items-center',

            // Font
            'text-sm',

            // Spacing
            'm-0 px-4 py-3',
            // 'first:mt-0 mt-[2px]',

            // Shape
            'border-0',

            // Colors
            {
                'text-gray-950': !context.focused && !context.selected,
                'bg-primary-100': context.focused && !context.selected,
                'text-primary-600': context.focused && !context.selected,

                'bg-primary-100 text-primary-600': context.selected
            },

            //States
            { 'hover:bg-gray-100': !context.focused && !context.selected },
            { 'hover:text-primary-600 hover:bg-primary-100': context.focused && !context.selected },

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
            'm-0 py-3 px-4',

            // Colors
            'bg-transparent',
            'text-gray-500',

            // Misc
            'cursor-auto'
        ]
    },
    optionCheckIcon: 'relative -ms-1.5 me-1.5 text-gray-700 w-4 h-4',
    optionBlankIcon: 'w-4 h-4',
    emptymessage: {
        class: [
            // Font
            'text-sm',

            // Spacing
            'py-3 px-4',

            // Color
            'text-gray-950',
            'bg-transparent'
        ]
    },
    header: {
        class: [
            // Spacing
            'pt-2 px-2 pb-0',
            'm-0',

            //Shape
            'border-b-0',
            'rounded-tl',
            'rounded-tr',

            // Color
            'text-gray-950',
            'bg-white',
            'border-gray-300'
        ]
    },
    clearIcon: {
        class: [
            // Color
            'text-surface-400',

            // Position
            'absolute',
            'top-1/2',
            'right-12',

            // Spacing
            '-mt-2'
        ]
    },
    loadingIcon: {
        class: 'text-surface-400 animate-spin'
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};

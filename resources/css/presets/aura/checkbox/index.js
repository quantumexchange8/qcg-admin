export default {
    root: {
        class: [
            'relative',

            // Alignment
            'inline-flex',
            'align-bottom',

            // Size
            // 'w-5 h-5',

            // Misc
            'cursor-pointer',
            'select-none'
        ]
    },
    box: ({ props, context }) => ({
        class: [
            // Alignment
            'flex',
            'items-center',
            'justify-center',

            // Size
            'w-full h-full',

            // Shape
            'rounded',
            'border',

            // Colors
            {
                'border-gray-300 bg-gray-100': props.disabled, // Gray color when disabled
                'border-gray-300': !context.checked && !props.invalid && !props.disabled,
                'bg-white': !context.checked && !props.invalid && !props.disabled,
                'border-primary bg-primary': context.checked && !props.disabled,
                'border-gray-300 bg-gray-100': context.checked && props.disabled // Gray color when checked but disabled
            },

            // Invalid State
            // 'invalid:focus:ring-error-500',
            // 'invalid:hover:border-error-500',
            { 'border-error-500': props.invalid },

            // States
            {
                'peer-hover:border-primary-500': !props.disabled && !context.checked && !props.invalid,
                'peer-hover:border-primary-600 peer-hover:bg-primary-600': !props.disabled && context.checked,
                'peer-focus-visible:z-10 peer-focus-visible:outline-none peer-focus-visible:outline-offset-0 peer-focus-visible:ring-1 peer-focus-visible:ring-primary-500': !props.disabled,
                'bg-gray-100 select-none pointer-events-none cursor-default': props.disabled
            },

            // Transitions
            'transition-colors',
            'duration-200'
        ]
    }),
    input: {
        class: [
            'peer',

            // Size
            'w-full ',
            'h-full',

            // Position
            'absolute',
            'top-0 left-0',
            'z-10',

            // Spacing
            'p-0',
            'm-0',

            // Shape
            'opacity-0',
            'rounded',
            'outline-none',
            'border border-gray-300',

            // Misc
            'appearance-none',
            'cursor-pointer'
        ]
    },
    icon: ({ props, context, state }) => ({
        class: [
            // Size
            'w-[85%]',
            'h-[85%]',
            'p-0.5',

            // Colors
            {
                'text-white': context.checked && !props.disabled && !props.invalid,
                'text-gray-500': context.checked && props.disabled && !props.invalid, // Gray color when disabled or invalid
                'text-primary': state.d_indeterminate
            },

            // Transitions
            'transition-all',
            'duration-200'
        ]
    })
};

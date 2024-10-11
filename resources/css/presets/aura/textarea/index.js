export default {
    root: ({ context, props, parent }) => ({
        class: [
            // Font
            'caret-primary-500 text-sm',

            // Flexbox
            'flex items-start resize-none',

            // Spacing
            'm-0',
            'py-3 px-4',

            // Shape
            'rounded',

            // Colors
            'text-gray-700',
            'placeholder:text-gray-500',
            { 'bg-white': !context.disabled },
            'border',
            { 'border-gray-300': !props.invalid },

            // Invalid State
            'invalid:focus:ring-error-500',
            'invalid:hover:border-error-500',
            { 'border-error-500': props.invalid },

            // States
            {
                'focus:z-10 hover:border-primary-500 focus:border-primary-500 focus:shadow-focus': !context.disabled && !props.invalid,
                'focus:outline-none focus:outline-offset-0 focus:ring-0 focus:ring-primary-500': !context.disabled,
                'bg-gray-200 select-none pointer-events-none cursor-default': context.disabled
            },

            // Filled State *for FloatLabel
            { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null && props.modelValue?.length !== 0 },

            // Misc
            'appearance-none shadow-input',
            'transition-colors duration-200'
        ]
    })
};

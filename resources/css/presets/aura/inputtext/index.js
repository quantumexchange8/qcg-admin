export default {
    root: ({ props, context, parent }) => ({
        class: [
            // Font
            'caret-primary-500 text-sm',

            // Flex
            { 'flex-1 w-[1%]': parent.instance.$name == 'InputGroup' },

            // Spacing
            'm-0',
            'w-full',

            // Size
            {
                'py-3 px-3.5': props.size == 'large',
                'py-1.5 px-2': props.size == 'small',
                'py-3 px-4': props.size == null,
                'py-3 px-10': props.size == 'search',
            },

            // Shape
            { 'rounded': parent.instance.$name !== 'InputGroup' },
            { 'first:rounded-l rounded-none last:rounded-r': parent.instance.$name == 'InputGroup' },
            { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
            { 'first:ml-0 -ml-px': parent.instance.$name == 'InputGroup' && !props.showButtons },

            // Colors
            'text-gray-950',
            'placeholder:text-gray-500',
            { 'bg-white': !context.disabled },
            'border',
            { 'border-gray-300': !props.invalid },

            // Invalid State
            // 'invalid:focus:ring-error-500',
            // 'invalid:hover:border-error-500',
            { 'border-error-500 focus:border-error-500': props.invalid },

            // States
            {
                'hover:border-primary-500 focus:border-primary-500 focus:shadow-focus': !context.disabled && !props.invalid,
                'focus:outline-none focus:outline-offset-0 focus:ring-0 focus:ring-primary-500 focus:z-10': !context.disabled,
                'bg-gray-100 select-none pointer-events-none cursor-default': context.disabled
            },

            // Filled State *for FloatLabel
            { filled: parent.instance?.$name == 'FloatLabel' && context.filled },

            // Misc
            'appearance-none shadow-input',
            'transition-colors duration-200'
        ]
    })
};

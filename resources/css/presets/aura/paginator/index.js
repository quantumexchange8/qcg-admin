export default {
    root: {
        class: [
            // Flex & Alignment
            'relative flex items-center justify-center flex-wrap',

            // Spacing
            'py-3',

            // Shape
            'border-0 rounded-md',

            // Color
            // 'bg-white',
            'text-gray-700',

            'text-sm',
            '[&_[data-pc-name=pcjumptopageinput]]:max-w-20'
        ]
    },
    content: ({ context }) => ({
        class: [
            'relative flex items-center justify-center flex-wrap',

            // Flex & Alignment
            'inline-flex items-center justify-center gap-x-0.5 md:gap-x-1 gap-y-3',

            '[&>[data-pc-section=current]]:text-gray-500',
            '[&>[data-pc-section=current]]:text-center',
            '[&>[data-pc-section=current]]:text-sm',
            '[&>[data-pc-section=current]]:min-w-full',
            'md:[&>[data-pc-section=current]]:min-w-fit',
        ],
    }),
    first: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center p-0 md:p-3 flex-wrap',

            // Shape
            'border-0 rounded-full',

            // Size
            'w-9 h-9 md:w-11 md:h-11',

            // Color
            'text-gray-700',

            // State
            {
                'hover:bg-gray-100': !context.disabled,
                'focus:outline-none focus:outline-offset-0': !context.disabled
            },

            // Transition
            'transition duration-200',

            // Misc
            'user-none overflow-hidden',
            { 'cursor-default pointer-events-none opacity-60': context.disabled }
        ]
    }),
    prev: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center p-0 md:p-3 flex-wrap',

            // Shape
            'border-0 rounded-full',

            // Size
            'w-9 h-9 md:w-11 md:h-11',

            // Color
            'text-gray-700',

            // State
            {
                'hover:bg-gray-100': !context.disabled,
                'focus:outline-none focus:outline-offset-0': !context.disabled
            },

            // Transition
            'transition duration-200',

            // Misc
            'user-none overflow-hidden',
            { 'cursor-default pointer-events-none opacity-60': context.disabled }
        ]
    }),
    next: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center p-0 md:p-3 flex-wrap',

            // Shape
            'border-0 rounded-full',

            // Size
            'w-9 h-9 md:w-11 md:h-11',

            // Color
            'text-gray-700',

            // State
            {
                'hover:bg-gray-100': !context.disabled,
                'focus:outline-none focus:outline-offset-0': !context.disabled
            },

            // Transition
            'transition duration-200',

            // Misc
            'user-none overflow-hidden',
            { 'cursor-default pointer-events-none opacity-60': context.disabled }
        ]
    }),
    last: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center p-0 md:p-3 flex-wrap',

            // Shape
            'border-0 rounded-full',

            // Size
            'w-9 h-9 md:w-11 md:h-11',

            // Color
            'text-gray-700',

            // State
            {
                'hover:bg-gray-100': !context.disabled,
                'focus:outline-none focus:outline-offset-0': !context.disabled
            },

            // Transition
            'transition duration-200',

            // Misc
            'user-none overflow-hidden',
            { 'cursor-default pointer-events-none opacity-60': context.disabled }
        ]
    }),
    pages: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center gap-0.5 md:gap-1 flex-wrap',
        ]
    }),
    page: ({ context }) => ({
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center flex-wrap',

            // Shape
            'border-0 rounded-full',
            'py-0 md:py-3',

            // Size
            'w-9 h-9 md:w-11 md:h-11',

            // Color
            'text-gray-700',

            // State
            {
                'hover:bg-gray-100': !context.disabled,
                'focus:outline-none focus:outline-offset-0': !context.disabled,
                'bg-primary-100 hover:bg-primary-100 text-primary-600': context.active
            },

            // Transition
            'transition duration-200',

            // Misc
            'user-none overflow-hidden',
            { 'cursor-default pointer-events-none opacity-60': context.disabled }
        ]
    }),
    contentStart: 'mr-auto',
    contentEnd: 'ml-auto'
};

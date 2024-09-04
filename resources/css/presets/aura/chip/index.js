export default {
    root: {
        class: [
            // Flexbox
            'inline-flex justify-center items-center',

            // Spacing
            'px-3 py-2 gap-2',

            // Shape
            'rounded',

            // Border
            'border border-gray-300',

            // Colors
            'text-gray-700',
            'bg-white',
            
            // Conditional classes based on state
            'hover:bg-gray-50 hover:cursor-pointer',

        ]
    },
    label: {
        class: 'text-center text-xs'
    },
    icon: {
        class: 'leading-6 mr-2'
    },
    image: {
        class: ['w-8 h-8 -ml-2 mr-2', 'rounded-full']
    },
    removeIcon: {
        class: [
            'inline-block',
            // Shape
            'rounded-md leading-6',

            // Size
            'w-4 h-4',

            // Transition
            'transition duration-200 ease-in-out',

            // Misc
            'cursor-pointer'
        ]
    }
};

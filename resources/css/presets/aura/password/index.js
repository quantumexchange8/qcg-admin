export default {
    root: ({ props }) => ({
        class: ['inline-flex relative w-full', { '[&>input]:pr-10': props.toggleMask }]
    }),
    overlay: {
        class: [
            // Spacing
            'p-3',

            // Shape
            'border',
            'shadow-md rounded-md',

            // Colors
            'bg-white',
            'text-gray-700',
            'border-gray-200'
        ]
    },
    meter: {
        class: [
            // Position and Overflow
            'overflow-hidden',
            'relative',

            // Shape and Size
            'border-0',
            'h-[10px]',
            'rounded-md',

            // Spacing
            'mb-3',

            // Colors
            'bg-gray-100'
        ]
    },
    meterLabel: ({ instance }) => ({
        class: [
            // Size
            'h-full',

            // Colors
            {
                'bg-error-500': instance?.meter?.strength == 'weak',
                'bg-orange': instance?.meter?.strength == 'medium',
                'bg-success-500': instance?.meter?.strength == 'strong'
            },

            // Transitions
            'transition-all duration-1000 ease-in-out'
        ]
    }),
    maskIcon: {
        class: ['absolute top-1/2 right-3 -mt-2 z-10', 'text-gray-500']
    },
    unmaskIcon: {
        class: ['absolute top-1/2 right-3 -mt-2 z-10', 'text-gray-500']
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};

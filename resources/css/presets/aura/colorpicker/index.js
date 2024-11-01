export default {
    root: ({ props }) => ({
        class: [
            // Display
            'inline-block',

            // Misc
            { 'opacity-60 select-none pointer-events-none cursor-default': props.disabled }
        ]
    }),
    preview: {
        class: [
            // Font
            'text-base leading-none',

            // Spacing
            'm-0',
            'p-0',

            //Size
            'w-6 h-6',

            // Shape
            'rounded-md',

            // Colors
            'bg-surface-0',
            'border border-surface-300',

            // States
            'hover:border-surface-400',
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 focus:z-10',

            // Transition
            'transition-colors duration-200',

            // Misc
            'cursor-pointer'
        ]
    },
    panel: ({ props }) => ({
        class: [
            // Position & Size
            {
                'relative h-[166px] w-[193px]': props.inline,
                'absolute h-[166px] w-[193px]': !props.inline
            },

            // Shape
            'shadow-md border',

            // Colors
            'bg-surface-800 border-surface-600'
        ]
    }),
    colorSelector: {
        class: [
            // Position
            'absolute top-[8px] left-[8px]',

            // Size
            'h-[150px] w-[150px]'
        ]
    },
    colorbackground: {
        class: 'w-[150px] h-[150px] bg-[linear-gradient(to_top,#000_0%,rgba(0,0,0,0)_100%),linear-gradient(to_right,#fff_0%,rgba(255,255,255,0)_100%)]',
    },
    colorHandle: {
        class: [
            'absolute',

            // Shape
            'rounded-full border border-solid',

            // Size
            'h-[10px] w-[10px]',

            // Spacing
            '-ml-[5px] -mt-[5px]',

            // Colors
            'border-white',

            // Misc
            'cursor-pointer opacity-85'
        ]
    },
    hue: {
        class: [
            // Position
            'absolute top-[8px] left-[167px]',

            // Size
            'h-[150px] w-[17px]',

            // Opacity
            'opacity-85'
        ],
        style: 'background: linear-gradient(0deg, red 0, #ff0 17%, #0f0 33%, #0ff 50%, #00f 67%, #f0f 83%, red)'
    },
    hueHandle: {
        class: [
            // Position
            'absolute left-0 -ml-[2px] -mt-[5px]',

            // Size
            'h-[10px] w-[21px]',

            // Shape
            'border-solid border-2',

            // Misc
            'cursor-pointer opacity-85'
        ]
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};

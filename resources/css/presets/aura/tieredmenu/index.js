export default {
    root: {
        class: [
            // Shape
            'rounded',

            // Size
            'min-w-[12rem] max-w-[200px]',
            'py-2',

            // Colors
            'bg-white',
            'border border-gray-200',
            'shadow-dropdown',
        ]
    },
    rootList: {
        class: [
            // Spacings and Shape
            'list-none',
            'flex flex-col',
            'm-0 p-0',
            'outline-none'
        ]
    },
    item: {
        class: 'relative my-[2px] [&:first-child]:mt-0'
    },
    itemContent: ({ context }) => ({
        class: [
            //Shape
            'rounded',

            // Colors
            {
                'text-gray-500 hover:text-gray-950': !context.focused && !context.active,
                'text-gray-700 bg-gray-100': context.focused && !context.active,
                'text-gray-950 bg-gray-100': !context.focused && context.active,
                'text-gray-900 bg-gray-100 hover:text-gray-950 hover:bg-gray-100': context.focused && context.active,
            },

            // Transitions
            'transition-shadow',
            'duration-200',

            // States
            {
                'hover:bg-gray-100': !context.active,
            },

            // Disabled
            { 'opacity-60 pointer-events-none cursor-default': context.disabled },
        ]
    }),
    itemLink: {
        class: [
            'relative',
            // Flexbox

            'flex',
            'items-center',

            // Spacing
            'py-2',
            'px-3',

            // Misc
            'no-underline',
            'overflow-hidden',
            'cursor-pointer',
            'select-none'
        ]
    },
    itemIcon: ({ context }) => ({
        class: [
            // Spacing
            'mr-2',
        ]
    }),
    itemLabel: {
        class: ['leading-none']
    },
    submenuIcon: {
        class: [
            // Position
            'ml-auto',
        ]
    },
    submenu: {
        class: [
            // Spacings and Shape
            'list-none',
            'flex flex-col',
            'm-0',
            'outline-none',
            'rounded',

            // Size
            'w-full',
            'py-2',

            // Position
            'absolute',
            'z-10',
            
            // Colors
            'bg-white',
            'border border-gray-200',
            'shadow-dropdown'
        ]
    },
    separator: {
        class: 'border-t border-gray-200'
    },
    transition: {
        enterFromClass: 'opacity-0',
        enterActiveClass: 'transition-opacity duration-250'
    }
};

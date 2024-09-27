export default {
    root: ({ props }) => ({
        class: [
            // Display and Position
            'flex',
            'max-w-full',
            'relative'
        ]
    }),
    pcInput: ({ props, parent }) => ({
        // root: {
        //     class: [
        //         // Display
        //         'flex-auto w-[1%]',

        //         // Font
        //         'leading-none',

        //         // Colors
        //         'text-gray-700',
        //         'placeholder:text-gray-400',
        //         { 'bg-white': !props.disabled },
        //         'border',
        //         { 'border-gray-300': !props.invalid },

        //         // Invalid State
        //         'invalid:focus:ring-red-200',
        //         'invalid:hover:border-red-500',
        //         { 'border-red-500': props.invalid },

        //         // Spacing
        //         'm-0 py-2 px-3',

        //         // Shape
        //         'appearance-none',
        //         { 'rounded-md': !props.showIcon || props.iconDisplay == 'input' },
        //         { 'rounded-l-md  flex-1 pr-9': props.showIcon && props.iconDisplay !== 'input' },
        //         { 'rounded-md flex-1 pr-9': props.showIcon && props.iconDisplay === 'input' },

        //         // Transitions
        //         'transition-colors',
        //         'duration-200',

        //         // States
        //         {
        //             'hover:border-gray-400': !props.disabled && !props.invalid,
        //             'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 focus:z-10': !props.disabled,
        //             'bg-gray-200 select-none pointer-events-none cursor-default': props.disabled
        //         },

        //         // Filled State *for FloatLabel
        //         { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null }
        //     ]
        // }
    }),
    pcPrevButton: ({ props, parent }) => ({
        root: {
            class: [
                // Display
                'flex',
                'justify-center',
                'items-center',

                // Colors
                'text-gray-700',
                'placeholder:text-gray-400',
                { 'bg-white': !props.disabled },

                // Spacing
                'm-0 py-2 px-3',

                // Shape
                'appearance-none',
                'rounded-full',

                // Transitions
                'transition-colors',
                'duration-200',
                
                // States
                {
                    'bg-transparent hover:bg-gray-100': !props.disabled && !props.invalid,
                    'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-gray-100 focus:z-10': !props.disabled,
                    'text-gray-400 bg-transparent select-none pointer-events-none cursor-default': props.disabled
                },

                // Filled State *for FloatLabel
                { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null }
            ]
        }
    }),
    pcNextButton: ({ props, parent }) => ({
        root: {
            class: [
                // Display
                'flex',
                'justify-center',
                'items-center',

                // Colors
                'text-gray-700',
                'placeholder:text-gray-400',
                { 'bg-white': !props.disabled },

                // Spacing
                'm-0 py-2 px-3',

                // Shape
                'appearance-none',
                'rounded-full',

                // Transitions
                'transition-colors',
                'duration-200',
                
                // States
                {
                    'bg-transparent hover:bg-gray-100': !props.disabled && !props.invalid,
                    'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-gray-100 focus:z-10': !props.disabled,
                    'text-gray-400 bg-transparent select-none pointer-events-none cursor-default': props.disabled
                },

                // Filled State *for FloatLabel
                { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null }
            ]
        }
    }),
    dropdownIcon: {
        class: ['absolute top-1/2 -mt-2', 'text-gray-400', 'right-3']
    },
    dropdown: {
        class: [
            'relative',

            // Alignments
            'items-center inline-flex text-center align-bottom justify-center',

            // Shape
            'rounded-r-md',

            // Size
            'py-2 px-0',
            'w-10',
            'leading-[normal]',

            // Colors
            'border border-l-0 border-gray-300',

            // States
            'focus:outline-none focus:outline-offset-0 focus:ring-1',
            'hover:bg-primary-hover hover:border-primary-hover',
            'focus:ring-primary-500'
        ]
    },
    inputIconContainer: 'absolute cursor-pointer top-1/2 right-3 -mt-3',
    inputIcon: 'inline-block text-gray-400 w-5 h-5',
    panel: ({ props }) => ({
        class: [
            // Display & Position
            {
                absolute: !props.inline,
                'inline-block': props.inline
            },

            // Size
            { 'w-auto mt-1 ': !props.inline },
            // { 'min-w-[80vw] w-auto': props.touchUI },
            { 'min-w-full': props.inline },
            'p-3',

            // Shape
            'border rounded-lg',
            {
                'shadow-md': !props.inline
            },

            // Colors
            'bg-white',
            'border-gray-200',

            //misc
            { 'overflow-x-auto': props.inline }
        ]
    }),
    header: {
        class: [
            // Flexbox and Alignment
            'flex items-center justify-between',

            // Spacing
            'p-0 pb-2',
            'm-0',

            // Shape
            'border-b',

            // Colors
            'text-gray-700',
            'bg-white',
            'border-gray-200'
        ]
    },
    title: {
        class: [
            // Flex
            'flex',
            'justify-center',
            'items-center',
            
            // Text
            'text-center',
            'mx-auto my-0'
        ]
    },
    selectMonth: {
        class: [
            // Flex
            'flex',

            // Spacing
            'px-3 py-2',
            'justify-center',
            'items-center',

            // Shape
            'rounded',

            // Font
            'text-center',
            'text-sm',
            'font-semibold',

            // Colors
            'text-gray-700',
            'hover:bg-primary-50',

            // Transitions
            'transition duration-200',

            // States
            'hover:text-primary-500',
            'focus:outline-none focus:outline-offset-0 focus:ring-0 focus:ring-primary-500 focus:z-10',

            // Misc
            'cursor-pointer'
        ]
    },
    selectYear: {
        class: [
            // Flex
            'flex',

            // Spacing
            'px-3 py-2',
            'justify-center',
            'items-center',

            // Shape
            'rounded',

            // Font
            'text-center',
            'text-sm',
            'font-semibold',

            // Colors
            'text-gray-700',
            'hover:bg-primary-50',

            // Transitions
            'transition duration-200',

            // States
            'hover:text-primary-500',
            'focus:outline-none focus:outline-offset-0 focus:ring-0 focus:ring-primary-500 focus:z-10',

            // Misc
            'cursor-pointer'
        ]
    },
    table: {
        class: [
            // Font
            'text-base leading-[normal]',
            // Size & Shape
            'border-collapse',
            'w-full',

            // Spacing
            'm-0 mt-2'
        ]
    },
    tableHeaderCell: {
        class: [
            // Spacing
            'p-1',
            'font-medium'
        ]
    },
    weekHeader: {
        class: ['leading-5', 'text-gray-700', 'opacity-60 cursor-default']
    },
    weekNumber: {
        class: ['text-gray-700', 'opacity-60 cursor-default']
    },
    weekday: {
        class: [
            'text-sm',

            // Colors
            'text-gray-700',
            'font-bold',
            'p-1'
        ]
    },
    dayCell: {
        class: [
            // Spacing
            'p-1'
        ]
    },
    weekLabelContainer: {
        class: [
            // Flexbox and Alignment
            'flex items-center justify-center',
            'mx-auto',

            // Shape & Size
            'w-8 h-8',
            'rounded-full',
            'border-transparent border',
            'leading-[normal]',

            // Colors
            'opacity-60 cursor-default'
        ]
    },
    dayView: 'w-full',
    day: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'justify-center',
            'grow-0 shrink-0',
            'mx-auto',

            // Shape & Size
            'w-8 h-8',
            'rounded-full',
            'border-transparent border',
            'text-sm',

            // Colors
            {
                'bg-gray-200 text-gray-700': context.date.today && !context.selected && !context.disabled,
                'bg-transparent text-gray-700': !context.selected && !context.disabled && !context.date.today,
                'bg-primary-100 text-primary-600': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0',
            'focus:ring-1 focus:ring-primary-500 focus:z-10',
            {
                'hover:bg-gray-100': !context.selected && !context.disabled
            },
            {
                'opacity-60 text-gray-400 cursor-default': context.disabled,
                'cursor-pointer': !context.disabled
            }
        ]
    }),
    monthView: {
        class: [
            // Spacing
            'mt-3',
        ]
    },
    month: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'inline-flex items-center justify-center',

            // Size
            'w-1/3',
            'p-1',

            // Text
            'text-sm',

            // Shape
            'rounded',

            // Colors
            {
                'text-gray-700 bg-transparent': !context.selected && !context.disabled,
                'bg-primary-50 text-primary-600': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0',
            // 'focus:ring-1 focus:ring-primary-500 focus:z-10',
            {
                'hover:bg-gray-100': !context.selected && !context.disabled
            },

            // Misc
            'cursor-pointer'
        ]
    }),
    yearView: {
        class: [
            // Spacing
            'mt-3'
        ]
    },
    year: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'inline-flex items-center justify-center',

            // Size
            'w-1/2',
            'p-1',

            // Text
            'text-sm',

            // Shape
            'rounded',

            // Colors
            {
                'text-gray-700 bg-transparent': !context.selected && !context.disabled,
                'bg-primary-50 text-primary-600': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0',
            // 'focus:ring-1 focus:ring-primary-500 focus:z-10',
            {
                'hover:bg-gray-100': !context.selected && !context.disabled
            },

            // Misc
            'cursor-pointer'
        ]
    }),
    timePicker: {
        class: [
            // Flexbox
            'flex',
            'justify-center items-center',

            // Borders
            'border-t-1',
            'border-solid border-gray-200',

            // Spacing
            'pt-2 mt-2'
        ]
    },
    separatorContainer: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    separator: {
        class: [
            // Text
            'text-xl'
        ]
    },
    hourPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    minutePicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    secondPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    ampmPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    calendarContainer: 'flex',
    calendar: 'flex-auto border-l first:border-l-0 border-gray-200',
    buttonbar: {
        class: [
            // Flexbox
            'flex justify-between items-center',

            // Spacing
            'pt-2',

            // Shape
            'border-t border-gray-200'
        ]
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};

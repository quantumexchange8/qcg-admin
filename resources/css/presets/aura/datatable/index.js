export default {
    root: ({ props }) => ({
        class: [
            'relative',

            // Flex & Alignment
            { 'flex flex-col': props.scrollable && props.scrollHeight === 'flex' },

            // Size
            { 'h-full': props.scrollable && props.scrollHeight === 'flex' },
            'w-full',
            
            '[&>[data-pc-section=paginatorcontainer]]:mt-5',
            '[&>[data-pc-section=paginatorcontainer]]:md:mt-6',
        ]
    }),
    mask: {
        class: [
            // Position
            'absolute',
            'top-0 left-0',
            'z-20',

            // Flex & Alignment
            'flex items-center justify-center',

            // Size
            'w-full h-full',

            // Color
            'bg-gray-100/60',

            // Transition
            'transition duration-200'
        ]
    },
    loadingIcon: {
        class: 'w-8 h-8 animate-spin'
    },
    tableContainer: ({ props }) => ({
        class: [
            { relative: props.scrollable, 'flex flex-col grow': props.scrollable && props.scrollHeight === 'flex' },

            // Size
            { 'h-full': props.scrollable && props.scrollHeight === 'flex' }
        ]
    }),
    header: ({ props }) => ({
        class: [
            'font-bold',

            // Shape
            props.showGridlines ? 'border-x border-t border-b-0' : 'border-y border-x-0',

            // Color
            'bg-white',
            'border-transparent',
            'text-gray-700'
        ]
    }),
    table: {
        class: 'w-full border-spacing-0 border-separate'
    },
    thead: ({ context }) => ({
        class: [
            {
                'bg-white top-0 z-10 sticky': context.scrollable
            }
        ]
    }),
    tbody: ({ instance, context }) => ({
        class: [
            {
                'sticky z-20': instance.frozenRow && context.scrollable
            },
            'bg-white'
        ]
    }),
    tfoot: ({ context }) => ({
        class: [
            {
                'bg-white bottom-0 z-0': context.scrollable
            }
        ]
    }),
    emptymessagecell: {
        class: 'bg-white'
    },    
    footer: {
        class: [
            'font-bold',

            // Shape
            'border-t-0 border-b border-x-0',

            // Spacing
            'p-4',

            // Color
            'bg-white',
            'border-gray-200',
            'text-gray-700'
        ]
    },
    column: {
        headerCell: ({ context, props }) => ({
            class: [
                'text-gray-950',
                'font-bold',
                'text-xs',
                'uppercase',
                
                // Position
                { 'sticky z-10 border-b': props.frozen || props.frozen === '' },

                { relative: context.resizable },

                // Alignment
                'text-left',

                // Shape
                { 'first:border-l border-y border-r': context?.showGridlines },
                'border-0 border-b border-solid',

                // Spacing
                context?.size === 'small' ? 'py-[0.375rem] px-2' : context?.size === 'large' ? 'py-[0.9375rem] px-5' : 'py-4 px-3',

                // Color
                (props.sortable === '' || props.sortable) && context.sorted ? 'bg-primary-100 text-primary-600' : 'bg-gray-50 text-gray-700',
                'border-transparent ',

                // States
                { 'hover:bg-gray-100': (props.sortable === '' || props.sortable) && !context?.sorted },
                'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-primary-500',

                // Transition
                { 'transition duration-200': props.sortable === '' || props.sortable },

                // Misc
                { 'cursor-pointer': props.sortable === '' || props.sortable },
                {
                    'overflow-hidden whitespace-nowrap border-y bg-clip-padding': context?.resizable // Resizable
                }
            ]
        }),
        columnHeaderContent: {
            class: 'flex items-center gap-2',
        },
        sort: ({ context }) => ({
            class: [context.sorted ? 'text-primary-600' : 'text-gray-700']
        }),
        bodyCell: ({ props, context, state, parent }) => ({
            class: [
                // Font
                'text-gray-950',
                'text-sm',

                //Position
                { 'sticky box-border border-b': parent.instance.frozenRow },
                { 'sticky box-border border-b z-20': props.frozen || props.frozen === '' },

                // Alignment
                'text-left',

                // Shape
                'border-0 border-b border-solid',
                { 'first:border-l border-r border-b': context?.showGridlines },
                { 'bg-white': parent.instance.frozenRow || props.frozen || props.frozen === '' },

                // Spacing
                { 'py-[0.375rem] px-2': context?.size === 'small' && !state['d_editing'] },
                { 'py-[0.9375rem] px-5': context?.size === 'large' && !state['d_editing'] },
                { 'py-2 px-3': context?.size !== 'large' && context?.size !== 'small' && !state['d_editing'] },
                { 'py-[0.6rem] px-2': state['d_editing'] },

                // Color
                'border-gray-100',

                {
                    'overflow-hidden whitespace-nowrap border-y bg-clip-padding': parent.instance?.$parentInstance?.$parentInstance?.resizableColumns // Resizable
                }
            ]
        }),
        footerCell: ({ context }) => ({
            class: [
                // Font
                'font-semibold',
                'text-sm',

                // Alignment
                'text-left',

                // Shape
                'border-0 border-b border-solid',
                { 'border-x border-y': context?.showGridlines },

                // Spacing
                context?.size === 'small' ? 'p-2' : context?.size === 'large' ? 'p-5' : 'p-4',

                // Color
                'border-gray-100',
                'text-gray-950',
                'bg-white'
            ]
        }),
        sortIcon: ({ context }) => ({
            class: [
                'w-4 h-4',
                context.sorted ? 'text-inherit' : 'text-gray-700'

            ]
        }),
        columnFilter: {
            class: 'inline-flex items-center ml-auto font-normal'
        },
        filterOverlay: {
            class: [
                'flex flex-col gap-2',

                // Position
                'absolute top-0 left-0',

                // Shape
                'border-0',
                'rounded-md',
                'shadow-md',

                // Size
                // 'min-w-[12.5rem]',

                // Color
                'bg-white',
                'text-gray-800',
            ]
        },
        filterConstraintList: {
            class: 'm-0 p-0 py-3 list-none'
        },
        filterConstraint: ({ context }) => ({
            class: [
                // Font
                'font-normal',
                'leading-none',

                // Position
                'relative',

                // Shape
                'border-0',
                'rounded-none',

                // Spacing
                'm-0',
                'py-3 px-5',

                // Color
                { 'text-gray-700': !context?.highlighted },
                { 'bg-white text-gray-700': !context?.highlighted },
                { 'bg-highlight': context?.highlighted },

                //States
                { 'hover:bg-gray-100': !context?.highlighted },
                { 'hover:text-gray-700 hover:bg-gray-100': !context?.highlighted },
                'focus-visible:outline-none focus-visible:outline-offset-0 focus-visible:ring-1 focus-visible:ring-inset focus-visible:ring-primary-500',

                // Transitions
                'transition-shadow',
                'duration-200',

                // Misc
                'cursor-pointer',
                'overflow-hidden',
                'whitespace-nowrap'
            ]
        }),
        filterOperator: {
            class: [
                // Shape
                'rounded-t-md',

                // Color
                'text-gray-700',
                'bg-white',
                '[&>[data-pc-name=pcfilteroperatordropdown]]:w-full'
            ]
        },
        filter: ({ instance }) => ({
            class: [{ 'flex items-center w-full gap-2': instance.display === 'row', 'inline-flex ml-auto': instance.display === 'menu' }]
        }),
        filterRule: 'flex flex-col gap-2',
        filterButtonbar: 'flex items-center justify-between p-0',
        filterAddButtonContainer: '[&>[data-pc-name=pcfilteraddrulebutton]]:w-full',
        rowToggleButton: {
            class: [
                'relative',

                // Flex & Alignment
                'inline-flex items-center justify-center',
                'text-left',

                // Spacing
                'm-0 p-0',

                // Size
                'w-8 h-8',

                // Shape
                'border-0 rounded-full',

                // Color
                'text-gray-500',
                'bg-transparent',
                'focus-visible:outline-none focus-visible:outline-offset-0',
                'focus-visible:ring-1 focus-visible:ring-primary-500',

                // Transition
                'transition duration-200',

                // Misc
                'overflow-hidden',
                'cursor-pointer select-none'
            ]
        },
        pcRowEditorSave: {
            root: {
                class: [
                    'relative',

                    // Flex & Alignment
                    'inline-flex items-center justify-center',
                    'text-left',
    
                    // Size
                    'w-9 h-9',
    
                    // Shape
                    'border-0 rounded-full',
    
                    // Color
                    'text-gray-500',
                    'border-transparent',
    
                    // States
                    'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500',
                    'hover:text-gray-700',
    
                    // Transition
                    'transition duration-200',
    
                    // Misc
                    'overflow-hidden',
                    'cursor-pointer select-none'
                ]
            }
        },
        pcRowEditorCancel: {
            root: {
                class: [
                    'relative',
    
                    // Flex & Alignment
                    'inline-flex items-center justify-center',
                    'text-left',
    
                    // Size
                    'w-8 h-8',
    
                    // Shape
                    'border-0 rounded-full',
    
                    // Color
                    'text-gray-500',
                    'border-transparent',
    
                    // States
                    'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500',
                    'hover:text-gray-700',
    
                    // Transition
                    'transition duration-200',
    
                    // Misc
                    'overflow-hidden',
                    'cursor-pointer select-none'
                ]    
            }
        },
        columnResizer: {
            class: [
                'block',

                // Position
                'absolute top-0 right-0',

                // Sizing
                'w-2 h-full',

                // Spacing
                'm-0 p-0',

                // Color
                'border border-transparent',

                // Misc
                'cursor-col-resize'
            ]
        },
        transition: {
            class: 'p-4 flex flex-col gap-2',
            enterFromClass: 'opacity-0 scale-y-[0.8]',
            enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
            leaveActiveClass: 'transition-opacity duration-100 ease-linear',
            leaveToClass: 'opacity-0'
        }
    },
    bodyRow: ({ context, props }) => ({
        class: [
            // Color
            { 'bg-highlight': context.selected && props.highlightOnSelect },
            { 'bg-white text-gray-600': !context.selected },
            { 'font-bold bg-white z-20': props.frozenRow },
            { 'odd:bg-white odd:text-gray-600 even:bg-gray-50 even:text-gray-600': context.stripedRows },

            // State
            { 'hover:bg-gray-50 hover:text-gray-600': props.selectionMode && !context.selected },

            // Transition
            { 'transition duration-200': (props.selectionMode && !context.selected) || props.rowHover },

            // Misc
            { 'cursor-pointer': props.selectionMode }
        ]
    }),
    rowExpansion: {
        class: 'bg-white text-gray-600'
    },
    rowGroupHeader: {
        class: ['sticky z-20', 'bg-white text-gray-600']
    },
    rowGroupFooter: {
        class: ['sticky z-20', 'bg-white text-gray-600']
    },
    rowToggleButton: {
        class: [
            'relative',

            // Flex & Alignment
            'inline-flex items-center justify-center',
            'text-left',

            // Spacing
            'm-0 p-0',

            // Size
            'w-8 h-8',

            // Shape
            'border-0 rounded-full',

            // Color
            'text-gray-500',
            'bg-transparent',
            'focus-visible:outline-none focus-visible:outline-offset-0',
            'focus-visible:ring-1 focus-visible:ring-primary-500',

            // Transition
            'transition duration-200',

            // Misc
            'overflow-hidden',
            'cursor-pointer select-none'
        ]
    },
    rowToggleIcon: {
        class: 'inline-block w-4 h-4'
    },
    columnResizeIndicator: {
        class: 'absolute hidden w-[2px] z-10 bg-primary'
    }
};

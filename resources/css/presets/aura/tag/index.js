export default {
    root: ({ props }) => ({
        class: [
            //Font
            'text-xs font-bold',

            //Alignments
            'inline-flex items-center justify-center',

            //Spacing
            'px-2 py-1',

            //Shape
            {
                'rounded-sm': !props.rounded,
                'rounded-full': props.rounded
            },

            //Colors
            {
                'bg-highlight': props.severity === null || props.severity === 'primary',
                'text-green-700 bg-green-100': props.severity === 'success',
                'text-surface-700 bg-surface-100': props.severity === 'secondary',
                'text-blue-700 bg-blue-100': props.severity === 'info',
                'text-orange-700 bg-orange-100': props.severity === 'warn',
                'text-red-700 bg-red-100': props.severity === 'danger',
                'text-surface-0 bg-surface-900': props.severity === 'contrast'
            }
        ]
    }),
    value: {
        class: 'leading-normal'
    },
    icon: {
        class: 'mr-1 text-sm'
    }
};

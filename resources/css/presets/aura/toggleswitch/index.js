export default {
    root: ({ props }) => ({
        class: [
          'inline-block relative',
          'w-[35px] h-5',
          'p-0.5',
          'rounded-full',
          {
            // 'opacity-60 select-none pointer-events-none cursor-default': props.disabled
            'select-none pointer-events-none cursor-default': props.disabled
          }
        ]
    }),
    slider: ({ props }) => ({
        class: [
          // Position
          'absolute top-0 left-0 right-0 bottom-0',
          { 'before:transform before:translate-x-[calc(100%-2px)]': props.modelValue == props.trueValue },
          
          // Shape
          'rounded-full',
          
          // Before:
          'before:absolute before:top-1/2 before:left-[1.67px]',
          'before:-mt-2',
          'before:h-4 before:w-4',
          'before:rounded-full',
          'before:duration-200',
          
        // Handle Colors and Disabled State
        {
            'before:bg-white': !props.disabled, // Normal state color
            'before:bg-gray-500': props.disabled, // Disabled state color for the handle
        },

          // Colors
          'border',
          {
            'bg-gray-200': props.modelValue != props.trueValue && !props.disabled,
            'bg-primary': props.modelValue == props.trueValue && !props.disabled,
            'border-transparent': !props.invalid,
          },
          
          // Invalid State
          { 'border-error-500': props.invalid },
          
          // States
          { 'peer-hover:bg-gray-300': props.modelValue != props.trueValue && !props.disabled && !props.invalid },
          { 'peer-hover:bg-primary-600': props.modelValue == props.trueValue && !props.disabled && !props.invalid },
          { 'bg-gray-200': props.modelValue != props.trueValue && props.disabled && !props.invalid },
          { 'bg-gray-200': props.modelValue == props.trueValue && props.disabled && !props.invalid },
          'peer-focus-visible:ring-1 peer-focus-visible:ring-primary-500',
          
          // Transition
          'transition-colors duration-200',
          
          // Misc
          'cursor-pointer'
          
        ]
    }),
    input: {
        class: [
            'peer',

            // Size
            'w-full ',
            'h-full',

            // Position
            'absolute',
            'top-0 left-0',
            'z-10',

            // Spacing
            'p-0',
            'm-0',

            // Shape
            'opacity-0',
            'rounded-full',
            'outline-none',

            // Misc
            'appearance-none',
            'cursor-pointer'
        ]
    }
};

export default {
  root: ({ props, context }) => ({
    class: [
      'relative shrink-0',
      // Shape
      // 'border-b',
      // 'rounded-t-md',
      // Spacing
      'p-3',
      // '-mb-px',
      // Colors and Conditions
      'outline-transparent',
      'font-semibold',
      {
        'text-gray-700 hover:text-primary-500': !context.active,
        'text-primary-500 border-b-2 border-primary-500': context.active,
        'opacity-60 cursor-default user-select-none select-none pointer-events-none': props == null ? void 0 : props.disabled
      },
      // States
      'focus:outline-none focus:outline-offset-0 focus-visible:ring-1 ring-inset focus-visible:ring-primary-400 dark:focus-visible:ring-primary-300',
      // Transitions
      'transition-all duration-200',
      // Misc
      'cursor-pointer select-none whitespace-nowrap',
      'user-select-none'
    ]
  })
};

export default {
    root: ({ context }) => ({
        class: ['relative flex flex-auto items-center gap-3 last-of-type:flex-[initial]', { 'cursor-default pointer-events-none select-none opacity-60': context.disabled }, '[&_[data-pc-section=separator]]:has-[~[data-p-active=true]]:bg-primary-600']
    }),
    header: ({ props, context }) => ({
        class: [
          'inline-flex items-center border-0 cursor-pointer rounded-md outline-transparent bg-transparent p-0 gap-2',
          'focus:outline-none focus:outline-offset-0 focus-visible:ring-1 ring-inset focus-visible:ring-primary-400',
          { '!cursor-default': context.active },
          { 'cursor-auto': props.linear }
        ]
    }),
    number: ({ context }) => ({
        class: [
          // Flexbox
          'flex',
          'items-center',
          'justify-center',
          //Colors
          'border-solid border-2 border-gray-200',
          // Colors (Conditional)
          context.active ? 'text-gray-950' : 'text-gray-500',
          // Adjust colors as needed
          // Size and Shape
          'min-w-[28px]',
          'h-[28px]',
          'line-height-[2rem]',
          'rounded-full',
          // Text
          'text-sm',
          // Transitions
          'transition',
          'transition-colors',
          'transition-shadow',
          'duration-200'
        ]
    }),
    title: ({ context }) => ({
        class: [
          // Layout
          'block',
          'whitespace-nowrap',
          'overflow-hidden',
          'text-ellipsis',
          'max-w-full',
          // Text
          context.active ? 'text-gray-950' : 'text-gray-500',
          'font-medium',
          'text-sm',
          // Transitions
          'transition',
          'transition-colors',
          'transition-shadow',
          'duration-200'
        ]
    })
};

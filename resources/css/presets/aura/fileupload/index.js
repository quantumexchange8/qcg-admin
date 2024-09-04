export default {
    root: {
        class: 'w-full'
    },
    input: {
        class: 'hidden'
    },
    header: {
        class: [
            // Flexbox
            'flex',
            'flex-wrap',

            // Colors
            'bg-white',
            'text-gray-500',

            // // Spacing
            // 'p-[1.125rem]',
            'gap-2',

            // // Borders
            // 'border',
            // 'border-solid',
            // 'border-gray-200',
            // 'border-b-0',

            // Shape
            'rounded-tr',
            'rounded-tl'
        ]
    },
    content: {
        class: [
            // Position
            'relative',

            // Colors
            'bg-white',
            'text-gray-500',

            // // Spacing
            // 'p-[1.125rem]',

            // // Borders
            // 'border border-t-0',
            // 'border-gray-200',

            // Shape
            'rounded-b',

            //ProgressBar
            '[&>[data-pc-name=pcprogressbar]]:absolute',
            '[&>[data-pc-name=pcprogressbar]]:w-full',
            '[&>[data-pc-name=pcprogressbar]]:top-0',
            '[&>[data-pc-name=pcprogressbar]]:left-0',
            '[&>[data-pc-name=pcprogressbar]]:h-1',
            '[&>[data-pc-name=pcprogressbar]]:bg-primary-500'
        ]
    },
    file: {
        class: [
            // Flexbox
            'flex',
            'items-center',
            'gap-3',

            // Spacing
            'px-4 py-3',
            'my-2',
            'last:mb-0',

            // Color
            'border',
            'border-gray-200',

            // Shape
            'rounded',
            'w-full',

            // Badge
            '[&>[data-pc-name=pcfilebadge]]:hidden'

        ],
    },
    fileThumbnail: 'shrink-0',
    fileName: 'mb-2 break-all',
    fileSize: 'mr-2',
    fileInfo: 'w-full flex flex-col justify-center items-start',
    fileActions: 'w-full flex justify-end items-center',
};

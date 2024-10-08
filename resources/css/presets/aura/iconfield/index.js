export default {
    root: {
        class: [
            'relative w-full',

            '[&>[data-pc-name=inputicon]]:absolute',
            '[&>[data-pc-name=inputicon]]:top-1/2',
            '[&>[data-pc-name=inputicon]]:-mt-2',
            '[&>[data-pc-name=inputicon]]:text-gray-950/60',

            '[&>[data-pc-name=inputicon]:first-child]:left-3',
            '[&>[data-pc-name=inputicon]:last-child]:right-3',

            '[&>[data-pc-name=inputtext]:first-child]:pr-7',
            '[&>[data-pc-name=inputtext]:last-child]:pl-7',

            // filter
            '[&>[data-pc-extend=inputicon]]:absolute',
            '[&>[data-pc-extend=inputicon]]:top-1/2',
            '[&>[data-pc-extend=inputicon]]:-mt-2',
            '[&>[data-pc-extend=inputicon]]:text-gray-950/60',

            '[&>[data-pc-extend=inputicon]:first-child]:left-3',
            '[&>[data-pc-extend=inputicon]:last-child]:right-3',

            '[&>[data-pc-extend=inputtext]:first-child]:pr-7',
            '[&>[data-pc-extend=inputtext]:last-child]:pl-7',

        ]
    }
};

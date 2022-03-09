const path = require('path');
const {DefinePlugin} = require('webpack')
const CopyPlugin = require("copy-webpack-plugin")

const allowedPrefixes = ['AUTH0', 'TRUNKRS']
const definePublicEnvironment = () => {
    const variables = Object.keys(process.env)

    const allowedVars = variables.reduce(
        (vars, currentVar) => {
            const isAllowed = allowedPrefixes.some(prefix => currentVar.startsWith(prefix))
            if (isAllowed) {
                vars[currentVar] = process.env[currentVar]
            }

            return vars
        },
        {NODE_ENV: process.env.NODE_ENV},
    )

    return JSON.stringify(allowedVars)
}

module.exports = {
    entry: './assets/index.tsx',

    output: {
        path: path.join(__dirname, '..', 'view/adminhtml/web/js'),
        filename: 'trunkrs.bundle.js',
    },

    devServer: {
        liveReload: true,
    },

    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                    },
                    {
                        loader: 'ts-loader'
                    }
                ]
            },
            {
                test: /\.scss$/,
                use: [
                    "style-loader",
                    "css-loader",
                    "sass-loader",
                ],
            },
        ]
    },

    resolve: {
        extensions: [
            ".tsx",
            ".ts",
            ".js",
            ".jsx",
            ".svg",
        ],
    },

    plugins: [
        new DefinePlugin({
            'process.env': definePublicEnvironment(),
        }),
        new CopyPlugin({
            patterns: [
                {
                    from: "assets/icons/**",
                    to: "icons/[name].[ext]",
                },
            ],
        })
    ],
}

const path = require('path');

module.exports = {
    entry: './bootstrap-js/main.js',
    output: {
        filename: 'main.bundle.js',
        path: path.resolve(__dirname, 'public/assets/js'),
    },
    mode: 'production',
};
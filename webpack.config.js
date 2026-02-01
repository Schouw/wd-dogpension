const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const fs = require('fs');

// Fjern CleanWebpackPlugin fra default config
const filteredPlugins = defaultConfig.plugins.filter(
    (plugin) => plugin.constructor.name !== 'CleanWebpackPlugin'
);

const blocksDir = path.resolve(__dirname, 'blocks');

const blockFolders = fs.readdirSync(blocksDir).filter((folder) =>
    fs.statSync(path.join(blocksDir, folder)).isDirectory() &&
    folder !== 'utils'
);

module.exports = blockFolders.flatMap((folder) => {
    const blockPath = path.join(blocksDir, folder);
    const entries = [];

    const indexPath = path.join(blockPath, 'src', 'index.js');
    const viewPath = path.join(blockPath, 'src', 'view.js');

    if (fs.existsSync(indexPath)) {
        entries.push({
            entry: indexPath,
            outputFilename: 'index.js',
        });
    }

    if (fs.existsSync(viewPath)) {
        entries.push({
            entry: viewPath,
            outputFilename: 'view.js',
        });
    }

    return entries.map(({ entry, outputFilename }) => ({
        ...defaultConfig,
        entry,
        plugins: filteredPlugins, // Brug den rensede plugin-liste
        output: {
            filename: outputFilename,
            path: path.join(blockPath, 'build'),
        },
    }));
});

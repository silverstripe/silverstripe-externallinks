const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const config = [
  // Main JS bundle
  new JavascriptWebpackConfig('js', PATHS, 'silverstripe/externallinks')
    .setEntry({
      BrokenExternalLinksReport: `${PATHS.SRC}/js/BrokenExternalLinksReport.js`,
    })
    .getConfig(),
  // sass to css
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      BrokenExternalLinksReport: `${PATHS.SRC}/styles/BrokenExternalLinksReport.scss`,
    })
    .getConfig(),
];

module.exports = config;

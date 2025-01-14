const fs = require('fs');
const path = require('path');
const ReactRefreshWebpackPlugin = require('@pmmmwh/react-refresh-webpack-plugin');
const ReactRefreshTypeScript = require('react-refresh-typescript').default;

const { merge } = require('webpack-merge');
const baseConfig = require('./base.config.js');

const createStyledComponentsTransformer =
  require('typescript-plugin-styled-components').default;

let serverOptions;
if (fs.existsSync(path.resolve(__dirname, 'certs/key.pem'))) {
  serverOptions = {
    key: fs.readFileSync(path.resolve(__dirname, 'certs/key.pem')),
    cert: fs.readFileSync(path.resolve(__dirname, 'certs/cert.pem')),
  };
}

const clientDir = path.resolve(
  __dirname,
  '../../../plugin/src/Resources/js/client'
);

module.exports = merge(baseConfig, {
  mode: 'development',

  output: {
    filename: '[name].js',
    chunkFilename: '[name].js',
    publicPath: 'https://127.0.0.1:8080/',
    path: clientDir,
  },

  devServer: {
    host: '127.0.0.1',
    allowedHosts: 'all',
    hot: true,
    server: {
      type: 'https',
      options: serverOptions,
    },
    client: {
      webSocketURL: 'https://127.0.0.1:8080/ws',
    },
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
      'Access-Control-Allow-Headers':
        'X-Requested-With, content-type, Authorization',
    },
    static: {
      directory: clientDir,
    },
  },

  plugins: [new ReactRefreshWebpackPlugin()],

  module: {
    rules: [
      {
        test: /\.ts(x?)$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'ts-loader',
            options: {
              getCustomTransformers: () => ({
                before: [
                  ReactRefreshTypeScript(),
                  createStyledComponentsTransformer(),
                ],
              }),
              transpileOnly: true,
            },
          },
        ],
      },
    ],
  },

  devtool: 'eval-source-map',
});

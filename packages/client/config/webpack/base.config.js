const path = require('path');
const webpack = require('webpack');

module.exports = {
  mode: 'production',
  target: ['web', 'es5'],

  entry: {
    client: path.resolve(__dirname, '../../src/index.tsx'),
  },
  output: {
    filename: '[name].js',
    chunkFilename: '[name].js',
    publicPath: '/',
    path: path.resolve(__dirname, '../../../plugin/src/Resources/js/client'),
  },

  module: {
    rules: [
      {
        test: /\.jsx$/,
        use: [
          {
            loader: 'babel-loader',
            options: { presets: ['@babel/preset-env', '@babel/preset-react'] },
          },
        ],
      },
      {
        test: /\.css$/,
        use: ['style-loader', { loader: 'css-loader' }],
      },
      {
        test: /\.svg$/,
        loader: '@svgr/webpack',
      },
      {
        test: /\.(png|jpg|jpeg|gif)$/i,
        use: [{ loader: 'url-loader' }],
      },
    ],
  },

  devtool: false,
  plugins: [
    new webpack.DefinePlugin({
      'process.env.DEBUG_MODE': JSON.stringify(
        process.env.NODE_ENV === 'development'
      ),
    }),
    new webpack.ProvidePlugin({
      React: 'react',
    }),
  ],

  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.jsx'],
    alias: {
      '@config': path.resolve(__dirname, '../../config/'),
      '@editor': path.resolve(__dirname, '../../src/app/pages/forms/edit/'),
      '@components': path.resolve(__dirname, '../../src/app/components/'),
      '@ff-icons': path.resolve(__dirname, '../../src/assets/icons/'),
      '@ff-client': path.resolve(__dirname, '../../src/'),
    },
  },
};

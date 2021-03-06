const path = require('path');
const webpack = require('webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
  'entry': './src/components/index',
  'output': {
    'path': path.join(__dirname, 'dist/'),
    'filename': 'index.js'
  },
  'plugins': [
    new webpack.ProvidePlugin({
      '$': 'jquery',
      'jQuery': 'jquery',
      'jquery': 'jquery'
    }),
    new webpack.DefinePlugin({
      'process.env.NODE_ENV': JSON.stringify('production')
    }),
    new CopyWebpackPlugin([
      'src/.htaccess',
      'src/about.html',
      'src/overview.html',
    ]),
  ],
  'resolve': {
    'extensions': ['.js', '.jsx'],
    'alias': {
      'root': path.join(__dirname, ''),
      'appRoot': path.join(__dirname, '/src')
    }
  },
  'module': {
    'rules': [
      {
        'test': /\.s?css$/,
        'use': [
          'style-loader',
          'css-loader',
          'sass-loader'
        ]
      },
      {
        'test': /\.jsx?$/,
        'loader': 'babel-loader',
        'include': [path.join(__dirname, '/src'), path.join(__dirname, 'node_modules/reflux-core')],
        'options': {
          'presets': ['react', 'es2015', 'stage-1', 'stage-2']
        }
      },
      {
        'test': /\.(woff2?|png|jpe?g|ico|ttf|otf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
        'loader': 'file-loader',
        'options': {
          'name': '[name].[ext]',
          'outputPath': 'assets/'
        }
      },
      {
        'test': /\.html$/,
        'loader': 'file-loader?name=[name].[ext]'
      }
    ]
  }
}

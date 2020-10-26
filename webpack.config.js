const path = require('path')

module.exports = {
  entry: path.resolve(__dirname, 'protected', 'ReactAddons', 'src', 'index.js'),
  output: {
    path: path.resolve(__dirname, 'protected', 'ReactAddons', 'dist'),
    filename: 'react_bundle.js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: 'babel-loader'
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      },
      {
        test: /\.scss$/,
        use: ['style-loader', 'css-loader', 'sass-loader']
      }
    ]
  },
  devtool: "eval-cheap-module-source-map"
}
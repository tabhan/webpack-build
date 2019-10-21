# AAXISWebpackBundle

AAXISWebpackBundle supports [Webpack](https://webpack.js.org/) build.

## Commands
### `aaxis:webpack:dump` command

Generates dynamic info of webpack.config.
The output file is `<ProjectDir>/webpack.app.config.json`

### `aaxis:webpack:build` command

Execute webpack. Requires `<ProjectDir>/package.json` and `<ProjectDir>/webpack.app.config.js`.
Examples when use AAXISReatBundle.

#### package.json
```json
{
  "name": "aaxis-build",
  "description": "Build static resources",
  "license": "MIT",
  "main": "webpack.config.js",
  "devDependencies": {
    "path": "^0.12.7",
    "webpack": "^4.17.2",
    "webpack-cli": "^3.1.0",
    "webpack-merge": "^4.1.2",
    "@babel/core": "^7.6.4",
    "@babel/preset-env": "^7.6.3",
    "@babel/preset-react": "^7.6.3",
    "babel-loader": "^8.0.6",
    "react": "^16.10.2",
    "react-dom": "^16.10.2",
    "axios": "^0.18.0",
    "dva": "^2.2.3",
    "dva-loading": "^2.0.1"
  },
  "dependencies": {
    "@babel/plugin-proposal-class-properties": "^7.5.5",
    "@babel/plugin-proposal-decorators": "^7.6.0",
    "@babel/plugin-transform-runtime": "^7.6.2",
    "@babel/preset-stage-2": "^7.0.0",
    "underscore": "^1.9.1"
  }
}
```

#### webpack.app.config.js
```javascript
const config = require('./webpack.app.config.json');
const webpackMerge = require('webpack-merge');

module.exports = webpackMerge(config, {
    output: {
        filename: '[name].bundle.js',
        libraryTarget: 'amd'
    },
    externals: {
        react: {
            amd: 'react'
        },
        underscore: {
            amd: 'underscore'
        },
        'react-utils': {
            amd: 'react-utils'
        }
    },
    module: {
        rules: [{
            test: /\.jsx$/,
            loader: 'babel-loader',
            options: {
                presets: [
                    "@babel/preset-env", "@babel/preset-react"
                ]
            }
        }]
    },
    resolve: {
        alias: {
            'react-utils': './public/bundles/aaxisreact/js/common/dummy-object'
        }
    }
});
```
### Command listener

The bundle listens to event `console.comman`. 
Command `aaxis:webpack:build` will be triggered before `oro:assets:build`
Command `aaxis:webpack:dump` will be triggered before `aaxis:webpack:build`

## Entry Config

Entries are configured in file `<BundleName>/Resources/views/layouts/<ThemeName>/config/entry-points.yml`
Theme folder path will be prepended to entry names automatically to make sure the file will be generated to the theme folder.

There are files 
```
countries.jsx
orders.jsx
```
Under folder `AAXIS/Bundle/AppBundle/Resources/public/blank/js/react/components`
Content of file `AAXIS/Bundle/AppBundle/Resources/views/layouts/blank/config/entry-points.yml`
``` yaml
entry:
    js/react/components/countries: js/react/components/countries.jsx
    js/react/components/orders: js/react/components/orders.jsx
```
Execute command `aaxis:webpack:build`, files are generated to folder `AAXIS/Bundle/AppBundle/Resources/public/blank/js/react/components`
```
countries.bundle.js
orders.bundle.js
```
Instead of configure them one by one, you can use wildcard. The output is same:
```yaml
entry:
    js/react/components/*.jsx: ~
``` 
The entry configuration also supports array:
``` yaml
entry:
    all: [js/react/components/orders.jsx,js/react/components/countries.jsx]
```
The output file is `AAXIS/Bundle/AppBundle/Resources/public/blank/all.bundle.js`

// IE 10 polyfill
import 'core-js/es6/map';
import 'core-js/es6/set';
// IE 11 polyfill
import 'core-js/fn/string/includes';
import 'core-js/es7/array';
import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import api from "./utils/api";
import App from './App';
//import registerServiceWorker from './registerServiceWorker';

ReactDOM.render(<App />, document.getElementById(api.getAppContainerId()));
//registerServiceWorker();

import React, { Component } from 'react';
import './App.css';
import CalendarView from "./components/CalendarView";
import api from "./utils/api";

class App extends Component {
  render() {

    /**
     * Data source sample value.
     *
     * {
     *   "bundle_configuration": [{
     *     "entity_type_id": "node",
     *     "bundle_id": "event",
     *     "date_field_name": "field_date_range",
     *     "date_field_type": "daterange"
     *   }]
     * }
     *
     * @type {string}
     */
    const dataSource = api.getDataAttributeValue('data-source');
    const jsonDataSource = JSON.parse(dataSource);
    const defaultView = api.getDataAttributeValue('default-view');
    // Cast string as boolean.
    const languagePrefix = (api.getDataAttributeValue('language-prefix') === 'true');
    const languageId = api.getDataAttributeValue('language-id');

    return (
      <div className="App">
        {api.isDevEnvironment() ? (
          <div>
            <header className="App-header">
              <h1 className="App-title">React Calendar</h1>
            </header>
            <p className="App-intro">
            React progressive decoupling for Drupal 8 based on JSON API.
            </p>
          </div>
        ) : (
          <span />
        )}
        <CalendarView
          bundleConfiguration={jsonDataSource.bundle_configuration}
          defaultView={defaultView}
          languagePrefix={languagePrefix}
          languageId={languageId}
        />
      </div>
    );
  }
}

export default App;

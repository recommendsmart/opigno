import React from 'react';
import PropTypes from 'prop-types';
import BigCalendar from 'react-big-calendar';
import moment from 'moment';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import s from './CalendarView.css';
import api from '../utils/api.js';

class CalendarView extends React.Component {

  static propTypes = {
    bundleConfiguration: PropTypes.arrayOf(PropTypes.shape({
      entity_type_id: PropTypes.string.isRequired,
      bundle_id: PropTypes.string.isRequired,
      date_field_name: PropTypes.string.isRequired,
    }).isRequired),
    defaultView: PropTypes.string.isRequired,
    languagePrefix: PropTypes.bool.isRequired,
    languageId: PropTypes.string.isRequired,
  };

  /**
   * Callback of onSelectEvent.
   *
   * Go if the event detail page.
   *
   * @param entity_id
   * @param entity_type_id
   */
  static gotoEventPage(entity_id, entity_type_id = 'node') {
    // @todo path structure is subject to change depending on the entity type id.
    // @todo set language in path if languagePrefix set to true.
    window.location.href = `${api.getApiBaseUrl()}/${entity_type_id}/${entity_id}`;
  }

  constructor(props) {
    super(props);
    // Init to current date.
    const date = new Date();
    this.state = {
      dayView: 1,
      monthView: date.getMonth(),
      yearView: date.getFullYear(),
      events: [],
      hasError: false,
      isLoading: true,
    };
  }

  componentDidMount() {
    this.fetchEvents();
  }

  /**
   * Fetches events by endpoint.
   *
   * @param endpoint
   * @param bundleConfigurationIndex
   */
  fetchEventsByEndpoint(endpoint, bundleConfigurationIndex) {
    // Get field name from endpoint index.
    const dateField = this.props.bundleConfiguration[bundleConfigurationIndex].date_field_name;
    fetch(endpoint, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      // For Edge compliance.
      credentials: 'same-origin',
    })
      .then(response => {
        if (!response.ok) {
          throw Error(response.statusText);
        }
        return response;
      })
      .then(response => response.json())
      .then(jsonApiEvents => {
        // Filter events with no date.
        const filteredEvents = jsonApiEvents.data.filter((event) =>
          event.attributes[dateField] != null
        );
        const bundleEvents = [];
        // Map JSON API response to the structure expected by BigCalendar.
        filteredEvents.forEach(event => {
            // If no end_value is defined (type of is string),
            // the Drupal date field is from datetime type
            // and not daterange.
            // Set then the values to comply Big Calendar date in both
            // cases.
            let dateTimeValue = event.attributes[dateField].value;
            let dateTimeEndValue = null;
            if(typeof event.attributes[dateField] === "string") {
              dateTimeValue = event.attributes[dateField];
              dateTimeEndValue = dateTimeValue;
            }else {
              dateTimeValue = event.attributes[dateField].value;
              dateTimeEndValue = event.attributes[dateField].end_value;
            }

            bundleEvents.push(
              {
                // @todo generalize to other entity types than nodes
                id: event.attributes.drupal_internal__nid,
                title: event.attributes.title,
                // @todo set this property once available in Drupal
                allDay: false,
                // Convert date with timezone if any.
                // @todo use Drupal site wide configuration for the timezone #2
                start: moment(dateTimeValue, 'YYYY-MM-DDTHH:mm:ssZ').toDate(),
                end: moment(dateTimeEndValue, 'YYYY-MM-DDTHH:mm:ssZ').toDate(),
              }
            )
          }
        );
        const allEvents = this.state.events;
        allEvents.push(...bundleEvents);
        this.setState({events: allEvents});
      })
      .catch(() => this.setState({hasError: true}));
  }

  /**
   * Fetches events for all endpoints.
   */
  fetchEvents() {
    this.setState({events: [], isLoading: true});
    const endpoints = this.getJsonApiEndpoints();
    endpoints.forEach((endpoint, index) => {
      // @todo refactor by using pure function + async/await
      this.fetchEventsByEndpoint(endpoint, index);
    });
    this.setState({isLoading: false});
  }

  /**
   * Returns the JSON API endpoints for each configured bundle,
   * indexed by field instance.
   *
   * @returns {array}
   */
  getJsonApiEndpoints() {
    const { bundleConfiguration, languagePrefix, languageId } = this.props;
    let result = [];
    bundleConfiguration.forEach(bundleConfig => {
      const entityType = bundleConfig.entity_type_id;
      const bundle = bundleConfig.bundle_id;
      const dateField = bundleConfig.date_field_name;
      const dateFieldType = bundleConfig.date_field_type;

      const monthWithPadding = ("0" + (this.state.monthView + 1)).slice(-2);
      const viewedMonth = `${this.state.yearView}-${monthWithPadding}`;
      // Default to date.
      let params = `filter[date-filter][condition][path]=${dateField}&filter[date-filter][condition][operator]=STARTS_WITH&filter[date-filter][condition][value]=${viewedMonth}`;
      if (dateFieldType === 'daterange') {
        params = `filter[date-filter][condition][path]=${dateField}.value&filter[date-filter][condition][operator]=STARTS_WITH&filter[date-filter][condition][value]=${viewedMonth}`;
      }
      const baseUrlWithLanguagePrefix = languagePrefix ? `${api.getApiBaseUrl()}/${languageId}` : `${api.getApiBaseUrl()}`;
      result.push(`${baseUrlWithLanguagePrefix}/jsonapi/${entityType}/${bundle}?${params}`);
    });
    return result;
  }

  /**
   * Callback of onNavigate.
   *
   * @param date
   * @param view
   */
  onNavigate(date, view) {
    this.setState({
      yearView: date.getFullYear(),
      monthView: date.getMonth()
    // Set fetchEvents as a callback, so we wait for states.
    }, this.fetchEvents);
  }

  render() {

    const { defaultView } = this.props;

    // @todo localize
    if (this.state.hasError) {
      return <p>Error while loading events.</p>;
    }

    if (this.state.isLoading) {
      return <p>Loading events...</p>;
    }

    // Disable week, work week, day, agenda
    // @todo needs 'back' and 'next' handlers and Drupal configuration
    // @todo needs timezone support #2
    // @todo review https://github.com/intljusticemission/react-big-calendar/issues/867
    // let allViews = Object.keys(BigCalendar.Views).map(k => BigCalendar.Views[k]);
    let allViews = ['month'];
    BigCalendar.setLocalizer(BigCalendar.momentLocalizer(moment));

    return (
      <div className={s.container}>
        <BigCalendar
          date={new Date(this.state.yearView, this.state.monthView, this.state.dayView)}
          events={this.state.events}
          defaultView={defaultView}
          views={allViews}
          popup
          selectable={true}
          onSelectEvent={event => CalendarView.gotoEventPage(event.id)}
          onNavigate={(date, view) => this.onNavigate(date, view)}
        />
      </div>
    );
  }
}

export default CalendarView;

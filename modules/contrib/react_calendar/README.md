# React Calendar

React progressive decoupling for Drupal 8 based on JSON API.

## Dependencies

### React

- [Big Calendar](https://github.com/intljusticemission/react-big-calendar)
- [Moment](https://www.npmjs.com/package/react-moment)

## Drupal

- Date Range or Date (core)
- JSON API 2.x ([The path for JSON API to core](https://www.drupal.org/project/jsonapi/issues/2931785))

## Configuration 

- Enable the __Date Range__ or __Date__ module
- Add a _date range_ or _date_ field to a content type
- Use the content type tab to enable 'React Calendar' 
for the desired content type(s) and set the date field that will be used
- Head to _/react_calendar/calendar_
 
## Roadmap

By priority

- ~~Expose calendar as a block~~
- Content translation review for language fallback
 (/admin/config/react_calendar/settings)
- Configure events title display as multiline in month view 
- [Timezone support](https://github.com/r-daneelolivaw/react_calendar/issues/2)
- Provide configuration for BigCalendar React props,
including views (month, week, work week, day, agenda)
- Clear cache after module installation to display the React Calendar tab on the content type
- Set enabled entity types from the date or date range field instance definition instead of the
content type configuration that currently limits the exposed entities to nodes 
- Set Calendar view and date from the routing
- Configure Calendar exposed views (month, week, day, agenda)
- Add permission to view the calendar
- Week start day based on system configuration
- Provide configuration and translation for status messages (loading, error)
- Set calendar locales based on current interface language
- Optionally set event colors based on a taxonomy vocabulary (e.g. event type)
- Provide GraphQL implementation
- Choose between GraphQL or JSON API from the configuration
- Update events via drag and drop
- Styling documentation

## Development

### Drupal

- Enable the Date Range module
- Enable the React Calendar module
- Configure CORS to allow `* | http://localhost:3000`
- Create an 'event' content type with a 'date range' field 
named 'field_datetime_range'.
These values are coming from the static js/public/index.html file
- Use the event content type tab to enable 'React Calendar' 
and set the date field to the date range field previously created
- Devel generate some events

### React

1. cd in _react_calendar/js/src_
2. Install dependencies with `yarn install`
3. Copy the _constants/.env.example.js_ file to _constants/.env.local.js_ 
and set there your Drupal 8 development site url.
It will be used while debugging React as a standalone app for JSON API requests
4. Edit _App.js_ or the components
5. Run `yarn start` to start the React development server 
and test your app outside of Drupal (localhost:3000)
6. Run `php build.php` to bundle the dist js and css
that are referenced by _react_calendar.libraries.yml_

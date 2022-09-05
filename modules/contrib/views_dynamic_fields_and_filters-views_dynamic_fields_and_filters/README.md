# Views dynamic fields and filters

###### Enables site builders to easily enable or disable fields and filters in a view conditionally based on the values of exposed/contextual filters or basically any request parameters.

It is very flexible and can be used for any display type and any format that uses the fields defined in the view display.

### Idea

The initial idea was to build more convenient content browsers where you show specific fields and filters only if the relevant content type was selected without having to programm custom code.

With this resulting module there are endless use cases to apply this. In example:
- You have a JSON REST export made with views and wanted to show specific fields or apply a specific filter only if specific query parameters and values are supplied.
- You have a search page made with views and you wanted to show a "range filter" only if the user selected "search mode" to be "range" or show specific fields only if a user checked a checkbox "show details".
- Or express even more complex "funnels" i.e: Apply non-exposed filter "a" only if exposed filter "b" is one of "foo" or "bar" and exposed filter "c" is greater than 25

### Configuration:
##### General
When installing this module, a new section **Dynamic fields and filters** will be available at the advanced column on each display in the views UI.
You can define your base filters here and will also find detailed information about how to use them.

##### Configurate "base filters"
Based on "base filters" you can enable or disable fields and other filters.
You add a base filter by entering it´s "filter identifier" in the Dynamic fields and filters settings dialogue.
You find the “filter identifier” in the settings dialog of an exposed filter, it is representing the query parameter name of that filter.

Actually you can enter any GET or POST query parameter you want and use it as base filter, so this also works with contextual filters when using a query parameter for it.
For each display you can configurate a total of 9 base filters.


##### Condition syntax
Within the "administrative title" of a field or a filter you can use the condition syntax to only enable them if the base filter matches the given expression:

`dff{1..9}|expression|"Your custom Administrative title"`

Where `dff{1..9}` refers to a base filter.

`expression` is either the string value to match or a more specific expression.

in example:

`dff2|foobar|` expresses *base filter dff2 equals "foobar"*

*Always use the url-decoded version of the query value in expressions.
If in your url the parameter value is i.e `foo+bar+foobar` you would need to write:*

`dff2|foo bar foobar|` *to get a match*

##### Expression syntax
For more specific expressions you can use the expression syntax within a condition:

`dff{1..9}|{operator:expression}|`

Available expression operators are:

`{neq:expression}` - Not equals

`{in:expression,expression2,expression3}` - In array (delimiter is "," without a space)

`{nin:expression,expression2,expression3}` - Not in array

`{gt:expression}` - Greater than

`{lt:expression}` - Smaller than

`{cn:expression}` - Contains

`{ncn:expression}` - Not contains

If the query value itself is an array like in this example query:

`?types[]=foo&types[]=bar`
*url decoded for readability*

Each element will be evaluated, the expression is true if one the elements matches.

##### Chaining syntax
You can logically chain multiple conditions with the chaining syntax:

`dff{1..9}|expression|OPERATOR|dff{1..9}|expression|"Your custom Administrative title"`

Where `OPERATOR` is either `AND`, `OR` or `XOR` (uppercase), you can use a total of 10 operators per label, each must be followed by the `"|dff{1..9}|expression|"` pattern.

In example:

`dff2|{gt:5}|AND|dff4|{in:foo,bar}|OR|dff3|foobar|` *(dff2 is greater than 5 AND dff4 is one of "foo" or "bar") OR dff3 matches "foobar"*


*For detailed information on how they work, check out the php´s [Logical Operators](https://www.php.net/manual/en/language.operators.logical.php) The here provided operators work with the precedence like using "&&", "||" and "xor"*

You can use the condition syntax for the base filters as well, this way you can make even more complex chaining without writing long and repeating conditions.

##### In depth example: Show field "Author" only if exposed "Content type" filter is "Basic Page"
Lookup the "filter identifier" of your exposed "content type" filter, i.e `type` and enter it in this Dynamic fields and filters settings in the advanced tab in the tab "Base filters" i.e as `dff1`, then you lookup the value that is used when "Basic Page" is selected, i.e `page`. klick on the field "Author" and go to the tab "Administrative title" and enter:

`dff1|page| "Your custom Administrative title"`

### Installation
Install as you would normally install a Drupal module.

In example:
`composer require drupal/views_dynamic_fields_and_filters`

### Requirements

This module works out of the box on a minimal Drupal install as it only requires "Views" which is required by system.

##### Drupal Core:

Tested with core 9.3.x, 9.4.x, 10.0.0-alpha7 and php 8.1.0 & 7.4.26

Tested with core modules "Views REST export" & "Serialization" (They are not required to be enabled)

##### Contrib:

Compatible and tested with "Views Conditional"



##### todo:
- Test how low you can go on views version(determine core 7.x compatibility)
- Add functional tests.
- If query value is in array:
  - Make specific keys accessible with base filters.
  - Add "reversed expressions" to make lookups in the query array.
- Maybe add more expressions
- Maybe add inheritance from default-display, not too sure about this.

# Exposed Actions module

Drupal core and quite a few contrib modules come with Action plugins. They are handy tools to provide functionality in certain context, of which the most popular one are
- Rules
- Views Bulk Operations

Actions can also be triggered programatically - done so by many modules too.

However, there is no way to expose those actions to the UI for permitted users to directly trigger those actions where they make most sense: when viewing an entity of a certain type.

This is where this modules jumps in. It is very lightweight and does exactly that: expose actions as local task menu items such that they appear in the UI where they make sense. Simple setup requires just 3 steps:

1. Review your **list of actions** and probably create and configure new ones
2. Configure **user permissions** and define which role gets access to which action through the UI
3. The **permitted users will then see local actions** for those exposed actions when ever they view an entity and the local actions block is being configured for that view

It's as simple as that.

## GETTING STARTED

1. Install Exposed Actions in the usual way (https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
2. Go to Administration > Configuration > System > Actions (admin/config/system/actions)
3. Review the available action and add/configure new ones as you see need
4. Go to Administration > People > Permissions (admin/people/permissions#module-expose_actions)
5. Configure permissions for your actions and decide to which user roles they get exposed
6. Let your users access those actions easily

## LINKS

Project page: https://www.drupal.org/project/expose_actions
Submit bug reports, feature suggestions: https://www.drupal.org/project/issues/expose_actions

## MAINTAINERS

jurgenhaas - https://www.drupal.org/u/jurgenhaas

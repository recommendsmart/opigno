#Content as Configuration module

There are several entities which Drupal considers to be content
which are, in the main, site-structure configuration. These entities
include custom block content, menu links, taxonomy terms, and feeds,
among others. The “Content as Configuration” module allows you to
save the content of these entities as configuration items, which
means you can export them as YAML files and move them from one
environment to another.

This module started out as a fork of the
[structure_sync](https://drupal.org/project/structure_sync)
module, which (at the time of this writing) is not Drupal 9 compatible,
and additionally does not offer export of feeds. This turned into
a full module rewrite to take full advantage of modern PHP’s
more advanced object-oriented capabilities.

The module also exposes Drush commands to allow automation of these
import/export tasks.

##Drush comands

Available Drush commands are as follows:

- `drush content_as_config:import <entity-type> [--style=safe|full|force]` - Imports
  entities of the given type (block, menu, term, feed) using the
  given import style. (See below for an explanation of import
  styles.)

- `drush content_as_config:export <entity-type>` -
  Exports all entities of the given entity-type to configuration.

- `drush content_as_config:import-all [--style=safe|full|force]` -
  Performs import of all configured custom block content, menu links,
  taxonomy terms, and feeds, in the given style.

- `drush content_as_config:export-all` -
  Exports all custom block content, menu links, taxonomy terms,
  and feeds to configuration.

More granular control over what gets imported and exported may be
achieved via the admin UI at /admin/structure/content-as-config.

##Import styles

### Safe
This import style imports all entities of the given type which
have been exported to configuration, *unless* they already exist
in the current Drupal instance. Safe import *will not* update
already-existing content.

### Full
This import style will delete any entities of the given type that
are *not* in configuration. It will then perform a full insert +
update import.

### Force
Deletes *all* entities of the given type in the system (without
any further checks), and then performs an insert-only import from
configuration. *This is the nuclear option; use with care.*


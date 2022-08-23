CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module creates a system for managing per-user tasks on your website.

As these are usually simple and not intended to be accessed directly on their
own, they have been configured as Storage Entities, so they won't clutter up the
Add Content menu, need to be excluded from any site search configuration, etc.

In addition to creating a bundle for managing tasks, it also provides a view
block for displaying them, and a page view to allow drag-and-drop sorting using
DraggableViews.

An optional submodule is provided to allow better formtting, for example to show
a checkbox instead of a text link for marking tasks as done. This module is
optimized for the Olvero theme, but may be compatible with other themes too.

Another optional submodule provides an additional view, to expose the available
tasks in a REST endpoint, for example to be managed within a Javascript
component.


INSTALLATION
------------

 * Install the Tasks module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.
   We strongly recommend using composer to ensure all dependencies will be
   handled automatically.
 * This will import configuration. Once installed, the module doesn't really
   provide any additional functionality, so it can be uninstalled. Note that
   you won't be able to install it again on the same site, unless you delete the
   Task bundle and Tasks view that were installed originally.
 * There is also a Tasks Olivero submodule that is recommended if using Tasks on
   a site using the Olivero theme. If using a custom theme, you may need to
   implement similar CSS to what is found in the submodule.


REQUIREMENTS
------------

This module requires the Storage Entities, Add Content By Bundle, Display Link
Plus, and DraggableViews modules.


CONFIGURATION
-------------

 * Place the Tasks block wherever you want users to be able to create and manage
   their tasks. As part of the block configuration you can set the number of
   tasks to display.
 * For any role you want to give full use of the Tasks system, it should have
   the following permissions:
   * Access Draggableviews
   * Flag Status
   * Unflag Status
   * Add new Task Storage entities
   * Delete own Task Storage entities
   * Edit own Task Storage entities
   * View own unpublished Task Storage entities.
   * View published Task Storage entities.


MAINTAINERS
-----------

 * Current Maintainer: Martin Anderson-Clutz (mandclu) - https://www.drupal.org/u/mandclu

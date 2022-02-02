CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module provides a type of configuration entity to display entities in list
 form with plugins to manage each step of creating the list.

You have the possibility to create a list of any entity: Node, Media, Taxonomy 
Term, User, Files, etc.
The module allows you to: 
* Add a pager to your list.
* Choose the type of entity you want 
* Choose the language of the elements to be uploaded
* Choose the number of items to display per page

The display of the list is divided into regions: Header - Before - Content - 
After. 
You can place: The list items ( with the desired views mode), the total number 
of items, the pager, or your filters in one of these regions. 

Once your list is created, all you have to do is place it either through a block
 or return it as a field to an entity

REQUIREMENTS
------------
This module requires the following modules:
 * Layout Discovery (in core Drupal 8)
    
RECOMMENDED MODULES
-------------------
 * No specific recommendations, you can use this module as is.
 
INSTALLATION
------------
  * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

    
CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will prevent the links from appearing. To get the links
back, disable the module and clear caches.
    admin/structure/entity_list

If you want to customize the entity list display : 

`entity_list` define the plugin type `EntityListDisplay` to manage the list 
render.

You can extend `DefaultEntityListDisplay` or `EntityListDisplayBase` plugin to 
make your own.

Once the plugin is created, you can now choose your plugin in the display 
vertical tab in the entity list form.

Example of extending the `DefaultEntityListDisplay`:

```php
<?php

/**
 * Create Custom Entity List Display.
 *
 * @EntityListDisplay(
 *   id = "my_entity_list_display",
 *   label = @Translation("My entity list display")
 * )
 */
class MyEntityListDisplay extends DefaultEntityListDisplay {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      '#markup' => $this->t("This method is reponsible for creating the render array of the list."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state, EntityListInterface $entity_list) {
    $form = parent::settingsForm($form_state, $entity_list);
    // Add custom settings.
    return $form;
  }

}

```

MAINTAINERS
-----------

Current maintainers:
 * Marc-Antoine Marty (Martygraphie) - https://www.drupal.org/u/martygraphie
 * Leonard Treille (leonardT) - https://www.drupal.org/u/leonardt

This project has been sponsored by:
 * Ecedi

CONTENTS OF THIS FILE
---------------------

* Introduction
* Important
* Features
* Requirements
* Installation
* Customize

INTRODUCTION
------------

This module is designed to associate group specific comment with a group when
using the [Group](https://www.drupal.org/project/group) module.

IMPORTANT
---------
For the module to work, you should patch core with patch in [this ticket](https://www.drupal.org/project/drupal/issues/2879087)

FEATURES
--------
* Provides GroupContentEnabler for comment entity type.
* Supports posting comments and (updating, deleting) any/own comments per
  comment type.
* A comment posted on a commentable group, will automatically become an entity
  of the group itself.
* A comment posted on a commentable grouped entity, will automatically become
  an entity of every group the grouped entity belongs to.
* When removing the relation of a commentable entity from a group, comments
  belong to the entity will automatically be detached from the group. 
* Support 'skip comment approval' on the group level per comment type.

REQUIREMENTS
------------

 - Group module (https://drupal.org/project/group), version greater than
   8.x-1.2.
 - Comment module in core.

INSTALLATION
------------

Install the Group comment module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

CUSTOMIZE
---------

As comments are automatically added to groups, the module allows you to alter
the groups by using hook_group_comment_attach_groups_alter.

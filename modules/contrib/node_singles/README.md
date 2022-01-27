Node Singles
======================

> Singles are node types used for one-off pages that have unique content requirements

## Why?
- Singles are node types used for **one-off pages that have unique content requirements**, such as the homepage, an _About Us_ page, a _Contact Us_ page, etc.
- Unlike other node types, singles have **only one node** associated with them
- This concept was taken from [Craft CMS](https://docs.craftcms.com/v2/sections-and-entries.html#singles)

## Installation

This package requires PHP 7.1 and Drupal 8.8 or higher. It can be
installed using Composer:

```bash
 composer require drupal/node_singles
```

## How does it work?
When adding a new node type, check the _This is a content type with a single entity._
 checkbox under the _Singles_ tab to mark this node type as a single.

A node of this type will automatically be created and you will not be able 
to create others.

Only users with the `administer node singles` permission will be able to delete singles.

An overview with all singles is available at `/admin/content/singles` for 
all users with the `access node singles overview` permission. 

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

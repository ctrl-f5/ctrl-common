Ctrl RAD Bundle
===============

[![Build Status](https://travis-ci.org/ctrl-f5/ctrl-rad-bundle.svg?branch=master)](https://travis-ci.org/ctrl-f5/ctrl-rad-bundle)
[![Code Climate](https://codeclimate.com/github/ctrl-f5/ctrl-rad-bundle/badges/gpa.svg)](https://codeclimate.com/github/ctrl-f5/ctrl-rad-bundle)
[![Test Coverage](https://codeclimate.com/github/ctrl-f5/ctrl-rad-bundle/badges/coverage.svg)](https://codeclimate.com/github/ctrl-f5/ctrl-rad-bundle/coverage)

This bundle provides components to have a Symfony application up and running ASAP.

There is [a Fork available that implements this bundle][101] in the Symfony2 standard distribution.

What's inside?
--------------

* A layout based on [StartBootstrap Admin 2][2] including:
    - Twig Extensions for easy use of [Twitter Bootstrap][1] components suck as label and pagination.
    - easy configurable layout and templates for sidebar, topbar and breadcrumbs
    - [FOSUserBundle][102] template overrides
* Basic configurable CrudController
    - provides index and edit actions
    - index has a configurable grid with pagination and optional filtering
* EntityService layer
    - provides a layer between the controllers and doctrine to put business logic that doesn't fit in any entity
    - provides basic methods to fetch data, optionally filtered and sorted
    - find methods provide easy availability to pagination
    
SB Admin 2
----------

The default layout is [StartBootstrap Admin 2][2], which is included through [bower][3], so you can update it easily if needed.  

There are several blocks you can override to control different parts:
    - topbar
    - sidebar
    - breadcrumbs
    
The app title that's used in the topbar and the html title can be set through the twig variable `app_title`.  
This can also be set globally:

    twig:
      globals:
        app_title: "My App"
            
Twig Extensions
---------------

* Filters
    - label: converts a string or boolean to a bootstrap label, booleans are converted to yes or no
    - is_type: check if the variable is of a certain type
    - call: calls a php callable variable
* Functions
    - page_title: prints the html for a page title for bootstrap
    - pagination: prints the html for a pagination for bootstrap
    - is_type: check if the variable is of a certain type

[1]:    http://getbootstrap.com/
[2]:    http://startbootstrap.com/template-overviews/sb-admin-2/
[3]:    http://bower.io/
[101]:  https://github.com/ctrl-f5/symfony-standard
[102]:  https://github.com/FriendsOfSymfony/FOSUserBundle

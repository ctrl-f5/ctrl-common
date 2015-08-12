Ctrl Common
===============

[![Build Status](https://travis-ci.org/ctrl-f5/ctrl-common.svg)](https://travis-ci.org/ctrl-f5/ctrl-common) [![Code Climate](https://codeclimate.com/github/ctrl-f5/ctrl-common/badges/gpa.svg)](https://codeclimate.com/github/ctrl-f5/ctrl-common) [![Test Coverage](https://codeclimate.com/github/ctrl-f5/ctrl-common/badges/coverage.svg)](https://codeclimate.com/github/ctrl-f5/ctrl-common/coverage)

What's inside?
--------------

* Entity Services
    - Base class for Doctrine Entity based Domain Services
    - Doctrine Based Finder for Entity retrieval
* Criteria
    - Resolve array based criteria and apply them to sets
    - Resolver implementation that applies criteria to Doctrine QueryBuilder
* Tools
    - Extended Doctrine Paginator with extra features
    - Symfony Commands to dump or import to and from sql files
    
Symfony Configuration
---------------------

There is a yml file which loads up some config for symfony, like registering the commands.

To add this config, add the following file to your `config.yml`:

```yml
imports:
    ...
    - { resource: ../../vendor/ctrl-f5/ctrl-common/Resources/config/symfony_services_commands.yml }
```
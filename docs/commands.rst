Symfony Console Commands
========================

Currently there are 2 commands available:

* dump database
* import database dump

Both only work for MySQL

Configuration
-------------

There is a yml file which loads up some config for symfony, like registering the commands.

To add this config, add the following file to your ``app/config/config.yml``:

.. code-block:: yaml

    imports:
        - { resource: ../../vendor/ctrl-f5/ctrl-common/config/symfony_services.yml }

# Ctrl Common
=============

What's inside?
--------------

* :doc:`Entity Services </entity-services>`
    - Base class for Doctrine Entity based Domain Services
    - Doctrine Based Finder for Entity retrieval
    - Criteria
        + Resolve array based criteria and apply them to sets
        + Resolver implementation that applies criteria to Doctrine QueryBuilder
* :doc:`Tools </tools>`
    - Extended Doctrine Paginator with extra features
    - Symfony Commands to dump or import to and from sql files

:doc:`Read more about the commands </commands>`

.. toctree::
   :maxdepth: 2
   :caption: Components
    
   entity-services
   commands
   tools

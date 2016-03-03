Entity Services
===============

A basic Service layer defined by a simple interface:

.. code-block:: php

    interface ServiceInterface
    {
        public function getEntityClass();
    
        public function getRootAlias();
    
        public function assertEntityInstance($entity);
    
        public function getFinder();
    
        public function find(array $criteria = array(), array $orderBy = array());
    
        public function remove($idOrEntity, $failOnNotFound = false);
    
        public function persist($entity, $flush = true);
    
        public function flush();
    }

Since it's mainly designed to work with Doctrine ORM there are some methods that remind us of Doctrine concepts.

Currently only one concrete implementation is shipped. This implementation leaves one abstract function for the child to implement:

``getEntityClass()`` which expects the FQCN to be returned.

.. code-block:: php

    use Ctrl\Common\EntityService;
    
    class UserService extends AbstractDoctrineService
    {
        public function getEntityClass()
        {
            return User::class;
        }
    }

The ``find()`` method is a shortcut to the service's ``Finder::find()`` method.

Finder
------

The EntityService class delegates find operations to its Finder. This is a small layer on top of Doctrine to simplify queries to the database.

The Finder parses properties and automatically creates joins recursively. As an example, let's assume we have the following entities:

.. code-block :: php

    Article hasMany Comment
    Comment hasOne User
    
    
If we want to find all articles with comments by a certain user, we can write this in a single criteria:

.. code-block :: php

    $articleFinder->find(["article.comment.user" => $user->getId()]);
    
    // the root entity is also optional, so we could also write
    $articleFinder->find(["comment.user" => $user->getId()]);

values can be passing in several different ways:

.. code-block :: php

    // literals
    $finder->find('id = 1');
    
    // special checks
    $finder->find('id IS NULL');
    
    // key/values
    $finder->find(['id' => 1]);
    
    // key/values with unnamed parameters
    $finder->find(['id = ?' => 1]);
    
    // key/values with named parameters
    $finder->find(['id = :id' => ['id' => 1]]);
    
    // multiple unnamed parameters
    $finder->find(['id = ? and name = ?' => [1, 'john']]);
    
    // multiple mixed parameters
    $finder->find(['id = ? and name = :name' => [1, 'name' => 'john']]);
    
    // complex conditions
    $finder->find([
        'id = ? and (name = :name or email = :email)' => [
            1, 
            'name' => 'john', 
            'email' => 'john@doe.com'
        ]
    ]);
    
    // add a relations to join and select (eager loading)
    $finder->find(['id = 1', 'article.user.comments']);

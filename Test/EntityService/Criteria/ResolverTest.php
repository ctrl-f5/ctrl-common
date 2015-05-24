<?php

namespace Ctrl\Common\Test\Criteria;

use Ctrl\Common\Criteria\DoctrineResolver;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $rootAlias
     * @return DoctrineResolver
     */
    protected function getResolver($rootAlias)
    {
        return new DoctrineResolver($rootAlias);
    }

    public function test_root_alias_set_on_construct()
    {
        $resolver = $this->getResolver("myRootAlias");

        $this->assertEquals('myRootAlias', $resolver->getRootAlias());
    }

    public function test_set_root_alias()
    {
        $resolver = $this->getResolver("myRootAlias");

        $resolver->setRootAlias('newAlias');
        $this->assertEquals('newAlias', $resolver->getRootAlias());
    }

    public function test_unpack_conditions()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack('id = 1');
        $this->assertEquals(array(
            'root.id = 1',
        ), $result['conditions']);

        $result = $resolver->unpack('id = 1 and id IS NULL');
        $this->assertEquals(array(
            'root.id = 1',
            'root.id IS NULL'
        ), $result['conditions']);

        $result = $resolver->unpack(array('id = 1', 'id IS NULL'));
        $this->assertEquals(array(
            'root.id = 1',
            'root.id IS NULL'
        ), $result['conditions']);

        $result = $resolver->unpack('root.id = 1 and root.id IS NULL');
        $this->assertEquals(array(
            'root.id = 1',
            'root.id IS NULL'
        ), $result['conditions']);

        $result = $resolver->unpack('root.id = 1 and (id IS NULL or root.active = false)');
        $this->assertEquals(array(
            'root.id = 1',
            'root.id IS NULL or root.active = false'
        ), $result['conditions']);
    }

    public function test_unpack_condition_with_root_in_name()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack('rootAlias = 1');
        $this->assertEquals(array(
            'root.rootAlias = 1',
        ), $result['conditions']);
    }

    public function test_unpack_conditions_with_parameters()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('id' => 1));
        $this->assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('root.id' => 1));
        $this->assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('root.id' => 1, 'root.name' => 'tester'));
        $this->assertEquals(array(
            'root.id = ?1',
            'root.name = ?2',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            2 => 'tester',
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_named_parameters()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('id = :test' => 1));
        $this->assertEquals(array(
            'root.id = :test',
        ), $result['conditions']);
        $this->assertEquals(array(
            'test' => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('id = :test' => array('test' => 1)));
        $this->assertEquals(array(
            'root.id = :test',
        ), $result['conditions']);
        $this->assertEquals(array(
            'test' => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'id = :test' => 1,
            'name = :name' => 'tester',
        ));
        $this->assertEquals(array(
            'root.id = :test',
            'root.name = :name',
        ), $result['conditions']);
        $this->assertEquals(array(
            'test' => 1,
            'name' => 'tester',
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_mixed_parameters()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('id' => 1, 'name = :name' => 'tester'));
        $this->assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            'name' => 'tester',
        ), $result['parameters']);

        $result = $resolver->unpack(array('id' => 1, 'name = :name' => 'tester', 'parent' => 2));
        $this->assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
            'root.parent = ?2',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            'name' => 'tester',
            2 => 2,
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_multiple_parameters_in_single_condition()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('id = :id AND name = :name' => array('id' => 1, 'name' => 'tester')));
        $this->assertEquals(array(
            'root.id = :id',
            'root.name = :name',
        ), $result['conditions']);
        $this->assertEquals(array(
            'id' => 1,
            'name' => 'tester',
        ), $result['parameters']);

        $result = $resolver->unpack(array('id = :id AND active = true' => array('id' => 1)));
        $this->assertEquals(array(
            'root.id = :id',
            'root.active = true',
        ), $result['conditions']);
        $this->assertEquals(array(
            'id' => 1,
        ), $result['parameters']);
    }

    public function test_unpack_joins()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('messages'));
        $this->assertEquals(array(
            'root.messages' => 'messages',
        ), $result['joins']);
        $this->assertEquals(array(), $result['conditions']);

        $result = $resolver->unpack(array('messages.user'));
        $this->assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
        ), $result['joins']);
        $this->assertEquals(array(), $result['conditions']);

        $result = $resolver->unpack(array('orders.client.user'));
        $this->assertEquals(array(
            'root.orders' => 'orders',
            'orders.client' => 'client',
            'client.user' => 'user',
        ), $result['joins']);
        $this->assertEquals(array(), $result['conditions']);
    }

    public function test_unpack_joins_through_conditions()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array('messages.id' => 1));
        $this->assertEquals(array(
            'root.messages' => 'messages'
        ), $result['joins']);
        $this->assertEquals(array(
            'messages.id = ?1'
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1
        ), $result['parameters']);

        $result = $resolver->unpack(array('messages.user.id' => 1));
        $this->assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
        ), $result['joins']);
        $this->assertEquals(array(
            'user.id = ?1'
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'messages.user.id' => 1,
            'orders.client.id' => 2,
        ));
        $this->assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
            'root.orders' => 'orders',
            'orders.client' => 'client',
        ), $result['joins']);
        $this->assertEquals(array(
            'user.id = ?1',
            'client.id = ?2',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            2 => 2,
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'messages.user.id' => 1,
            'orders.client.id = :client' => 2,
        ));
        $this->assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
            'root.orders' => 'orders',
            'orders.client' => 'client',
        ), $result['joins']);
        $this->assertEquals(array(
            'user.id = ?1',
            'client.id = :client',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            'client' => 2,
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_mixed_parameters_and_joins()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array(
            'id' => 1,
            'name = :name' => 'tester',
            'relation',
        ));
        $this->assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            'name' => 'tester',
        ), $result['parameters']);
        $this->assertEquals(array(
            'root.relation' => 'relation',
        ), $result['joins']);

        $result = $resolver->unpack(array(
            'relationOne',
            'relationOne.test',
            'id' => 1,
            'name = :name' => 'tester',
            'relationTwo',
            'parent' => 2
        ));
        $this->assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
            'root.parent = ?2',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 1,
            'name' => 'tester',
            2 => 2,
        ), $result['parameters']);
        $this->assertEquals(array(
            'root.relationOne' => 'relationOne',
            'relationOne.test' => 'test',
            'root.relationTwo' => 'relationTwo',
        ), $result['joins']);
    }

    public function test_unpack_order_by()
    {
        $resolver = $this->getResolver("root");

        $result = $resolver->unpack(array(
            'id' => 'DESC',
            'name',
        ));
        $this->assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        $this->assertEquals(array(
            1 => 'DESC',
        ), $result['parameters']);
        $this->assertEquals(array(
            'root.name' => 'name',
        ), $result['joins']);
    }
}

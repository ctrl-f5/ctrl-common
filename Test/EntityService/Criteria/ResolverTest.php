<?php

namespace Ctrl\Common\Test\Criteria;

use Ctrl\Common\Criteria\DoctrineResolver;
use Ctrl\Common\Criteria\ResolverInterface;

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
        $resolver = $this->getResolver('myRootAlias');

        self::assertEquals('myRootAlias', $resolver->getRootAlias());
    }

    public function test_set_root_alias()
    {
        $resolver = $this->getResolver('myRootAlias');

        $resolver->setRootAlias('newAlias');
        self::assertEquals('newAlias', $resolver->getRootAlias());
    }

    public function test_create_graph()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->createGraph('test');
        self::assertEquals(['test'], $result);

        $result = $resolver->createGraph('(test)');
        self::assertEquals(['test'], $result);

        $result = $resolver->createGraph('(((test)))');
        self::assertEquals(['test'], $result);

        $result = $resolver->createGraph('(test)(test2)');
        self::assertEquals(['test', 'test2'], $result);

        $result = $resolver->createGraph('(test) and (test2)');
        self::assertEquals(['test', 'and', 'test2'], $result);

        $result = $resolver->createGraph('test (test2)');
        self::assertEquals(['test', 'test2'], $result);

        $result = $resolver->createGraph('(test (test2))');
        self::assertEquals([['test', 'test2']], $result);

        $result = $resolver->createGraph('(test) test3');
        self::assertEquals(['test', 'test3'], $result);

        $result = $resolver->createGraph('((test) test3)');
        self::assertEquals([['test', 'test3']], $result);

        $result = $resolver->createGraph('(test) and (test3 (test5))');
        self::assertEquals(['test', 'and', ['test3', 'test5']], $result);
    }

    public function test_tokenize()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->tokenize(['test']);
        self::assertEquals([[ResolverInterface::T_EXPR => 'test']], $result);

        $result = $resolver->tokenize(['test and test2']);
        self::assertEquals([
            [ResolverInterface::T_EXPR => 'test'],
            [ResolverInterface::T_AND => 'and'],
            [ResolverInterface::T_EXPR => 'test2']
        ], $result);

        $result = $resolver->tokenize(['test and test2 or test3']);
        self::assertEquals([
            [ResolverInterface::T_EXPR => 'test'],
            [ResolverInterface::T_AND => 'and'],
            [ResolverInterface::T_EXPR => 'test2'],
            [ResolverInterface::T_OR => 'or'],
            [ResolverInterface::T_EXPR => 'test3'],
        ], $result);
    }

    public function test_unpack_conditions()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack('id = 1');
        self::assertEquals([
            ['root.id = 1'],
        ], $result['conditions']);

        $result = $resolver->unpack('id = 1 and id IS NULL');
        self::assertEquals([
            [
            'root.id = 1 and ',
            'and',
            'root.id IS NULL',
            ]
        ], $result['conditions']);
//
//        $result = $resolver->unpack(array('id = 1', 'id IS NULL'));
//        self::assertEquals([
//            'root.id = 1',
//            'root.id IS NULL',
//        ], $result['conditions']);
//
//        $result = $resolver->unpack('root.id = 1 and root.id IS NULL');
//        self::assertEquals([
//            'root.id = 1',
//            'root.id IS NULL',
//        ], $result['conditions']);
//
//        $result = $resolver->unpack('root.id = 1 and (id IS NULL or root.active = false)');
//        self::assertEquals([
//            'and' => [
//                'root.id = 1',
//                'root.id IS NULL or root.active = false',
//            ]
//        ], $result['conditions']);
    }

    public function test_unpack_condition_with_root_in_name()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack('rootAlias = 1');
        self::assertEquals(array(
            'root.rootAlias = 1',
        ), $result['conditions']);
    }

    public function test_unpack_conditions_with_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('id' => 1));
        self::assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('root.id' => 1));
        self::assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('root.id' => 1, 'root.name' => 'tester'));
        self::assertEquals(array(
            'root.id = ?1',
            'root.name = ?2',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            2 => 'tester',
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_named_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('id = :test' => 1));
        self::assertEquals(array(
            'root.id = :test',
        ), $result['conditions']);
        self::assertEquals(array(
            'test' => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array('id = :test' => array('test' => 1)));
        self::assertEquals(array(
            'root.id = :test',
        ), $result['conditions']);
        self::assertEquals(array(
            'test' => 1,
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'id = :test' => 1,
            'name = :name' => 'tester',
        ));
        self::assertEquals(array(
            'root.id = :test',
            'root.name = :name',
        ), $result['conditions']);
        self::assertEquals(array(
            'test' => 1,
            'name' => 'tester',
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_mixed_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('id' => 1, 'name = :name' => 'tester'));
        self::assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            'name' => 'tester',
        ), $result['parameters']);

        $result = $resolver->unpack(array('id' => 1, 'name = :name' => 'tester', 'parent' => 2));
        self::assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
            'root.parent = ?2',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            'name' => 'tester',
            2 => 2,
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_multiple_parameters_in_single_condition()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('id = :id AND name = :name' => array('id' => 1, 'name' => 'tester')));
        self::assertEquals(array(
            'root.id = :id',
            'root.name = :name',
        ), $result['conditions']);
        self::assertEquals(array(
            'id' => 1,
            'name' => 'tester',
        ), $result['parameters']);

        $result = $resolver->unpack(array('id = :id AND active = true' => array('id' => 1)));
        self::assertEquals(array(
            'root.id = :id',
            'root.active = true',
        ), $result['conditions']);
        self::assertEquals(array(
            'id' => 1,
        ), $result['parameters']);
    }

    public function test_unpack_joins()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('messages'));
        self::assertEquals(array(
            'root.messages' => 'messages',
        ), $result['joins']);
        self::assertEquals(array(), $result['conditions']);

        $result = $resolver->unpack(array('messages.user'));
        self::assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
        ), $result['joins']);
        self::assertEquals(array(), $result['conditions']);

        $result = $resolver->unpack(array('orders.client.user'));
        self::assertEquals(array(
            'root.orders' => 'orders',
            'orders.client' => 'client',
            'client.user' => 'user',
        ), $result['joins']);
        self::assertEquals(array(), $result['conditions']);
    }

    public function test_unpack_joins_through_conditions()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array('messages.id' => 1));
        self::assertEquals(array(
            'root.messages' => 'messages'
        ), $result['joins']);
        self::assertEquals(array(
            'messages.id = ?1'
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1
        ), $result['parameters']);

        $result = $resolver->unpack(array('messages.user.id' => 1));
        self::assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
        ), $result['joins']);
        self::assertEquals(array(
            'user.id = ?1'
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'messages.user.id' => 1,
            'orders.client.id' => 2,
        ));
        self::assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
            'root.orders' => 'orders',
            'orders.client' => 'client',
        ), $result['joins']);
        self::assertEquals(array(
            'user.id = ?1',
            'client.id = ?2',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            2 => 2,
        ), $result['parameters']);

        $result = $resolver->unpack(array(
            'messages.user.id' => 1,
            'orders.client.id = :client' => 2,
        ));
        self::assertEquals(array(
            'root.messages' => 'messages',
            'messages.user' => 'user',
            'root.orders' => 'orders',
            'orders.client' => 'client',
        ), $result['joins']);
        self::assertEquals(array(
            'user.id = ?1',
            'client.id = :client',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            'client' => 2,
        ), $result['parameters']);
    }

    public function test_unpack_conditions_with_mixed_parameters_and_joins()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array(
            'id' => 1,
            'name = :name' => 'tester',
            'relation',
        ));
        self::assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            'name' => 'tester',
        ), $result['parameters']);
        self::assertEquals(array(
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
        self::assertEquals(array(
            'root.id = ?1',
            'root.name = :name',
            'root.parent = ?2',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 1,
            'name' => 'tester',
            2 => 2,
        ), $result['parameters']);
        self::assertEquals(array(
            'root.relationOne' => 'relationOne',
            'relationOne.test' => 'test',
            'root.relationTwo' => 'relationTwo',
        ), $result['joins']);
    }

    public function test_unpack_order_by()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->unpack(array(
            'id' => 'DESC',
            'name',
        ));
        self::assertEquals(array(
            'root.id = ?1',
        ), $result['conditions']);
        self::assertEquals(array(
            1 => 'DESC',
        ), $result['parameters']);
        self::assertEquals(array(
            'root.name' => 'name',
        ), $result['joins']);
    }
}

<?php

namespace Ctrl\Common\Test\Criteria;

use Ctrl\Common\Criteria\DoctrineResolver;
use Ctrl\Common\Criteria\ResolverInterface;
use Ctrl\Common\Test\EntityService\Criteria\Mock\QueryBuilder;

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

    public function test_tokenize()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->tokenize(['test']);
        self::assertEquals([[ResolverInterface::T_EXPR => 'test']], $result);

        $result = $resolver->tokenize(['test and test2']);
        self::assertEquals([
            [ResolverInterface::T_EXPR => 'test'],
            [ResolverInterface::T_AND => 'AND'],
            [ResolverInterface::T_EXPR => 'test2'],
        ], $result);

        $result = $resolver->tokenize(['test and test2 and']);
        self::assertEquals([
            [ResolverInterface::T_EXPR => 'test'],
            [ResolverInterface::T_AND => 'AND'],
            [ResolverInterface::T_EXPR => 'test2'],
            [ResolverInterface::T_AND => 'AND'],
        ], $result);
    }

    public function test_create_graph()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->createGraph('test');
        self::assertEquals([
            ResolverInterface::T_AND => ['test']
        ], $result);

        $result = $resolver->createGraph('test and (test2 or test3)');
        self::assertEquals([
            ResolverInterface::T_AND => [
                'test',
                [
                    ResolverInterface::T_OR => [
                        'test2',
                        'test3',
                    ]
                ]
            ],
        ], $result);
    }

    public function dataMultipleLogicalOnSameLevel()
    {
        return array(
            array('test and test2 or test3'),
            array('test or test2 and test3'),
            array('test and (test2 or test3 and test4)'),
            array('test and (test2 and test3 or test4)'),
        );
    }

    /**
     * @dataProvider dataMultipleLogicalOnSameLevel
     * @expectedException \Exception
     * @param string $string
     */
    public function test_create_graph_disallow_multiple_logical_on_same_level($string)
    {
        $resolver = $this->getResolver('root');

        $resolver->createGraph($string);
    }

    public function test_resolve_string()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve('id = 1');
        self::assertEquals([
            ResolverInterface::T_AND => ['root.id = 1' => null],
        ], $result['expressions']);

        $result = $resolver->resolve('id = 1 and id IS NULL');
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = 1' => null,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);

        $result = $resolver->resolve('id = 1 or id IS NULL');
        self::assertEquals([
            ResolverInterface::T_OR => [
                'root.id = 1' => null,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);

        $result = $resolver->resolve('root.id = 1 and root.id IS NULL');
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = 1' => null,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);
    }

    public function test_resolve_string_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve('root.id = 1 and (id IS NULL or root.active = false)');
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = 1' => null,
                [
                    ResolverInterface::T_OR => [
                        'root.id IS NULL' => null,
                        'root.active = false' => null,
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolve('root.id = 1 or (id IS NULL and root.active = false)');
        self::assertEquals([
            ResolverInterface::T_OR => [
                'root.id = 1' => null,
                [
                    ResolverInterface::T_AND => [
                        'root.id IS NULL' => null,
                        'root.active = false' => null,
                    ]
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_array()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(array('id = 1', 'id IS NULL'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = 1' => null,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(array('id = 1', 'test = 1 or tester = 2'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = 1' => null,
                ResolverInterface::T_OR => [
                    'root.test = 1' => null,
                    'root.tester = 2' => null,
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_string_with_value()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(array('id' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(array('id =' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(array('id = :id' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(['root.id in' => ['test']]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id IN ?' => 'test',
            ]
        ], $result['expressions']);
    }

    public function test_create_query_expression()
    {
        $resolver = $this->getResolver('root');

        $queryBuilder = new QueryBuilder();
        $expr = $resolver->createQueryExpression($queryBuilder, $resolver->resolve(array('id' => 1))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        /** @var \Doctrine\ORM\Query\Expr\Comparison $comp */
        $comp = $parts[0];
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Comparison', $parts[0]);
        self::assertEquals('root.id', $comp->getLeftExpr());
        self::assertEquals('=', $comp->getOperator());
        self::assertEquals('?1', $comp->getRightExpr());

        $queryBuilder = new QueryBuilder();
        $expr = $resolver->createQueryExpression($queryBuilder, $resolver->resolve(array('id =' => 1))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        /** @var \Doctrine\ORM\Query\Expr\Comparison $comp */
        $comp = $parts[0];
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Comparison', $parts[0]);
        self::assertEquals('root.id', $comp->getLeftExpr());
        self::assertEquals('=', $comp->getOperator());
        self::assertEquals('?1', $comp->getRightExpr());

        $queryBuilder = new QueryBuilder();
        $expr = $resolver->createQueryExpression($queryBuilder, $resolver->resolve(array('id = :id' => ['id' => 1]))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        /** @var \Doctrine\ORM\Query\Expr\Comparison $comp */
        $comp = $parts[0];
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Comparison', $parts[0]);
        self::assertEquals('root.id', $comp->getLeftExpr());
        self::assertEquals('=', $comp->getOperator());
        self::assertEquals(':id', $comp->getRightExpr());

        $queryBuilder = new QueryBuilder();
        $expr = $resolver->createQueryExpression($queryBuilder, $resolver->resolve(array('id IS NULL'))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        self::assertEquals('root.id IS NULL', $parts[0]);

        $queryBuilder = new QueryBuilder();
        $expr = $resolver->createQueryExpression($queryBuilder, $resolver->resolve(array('id IN :ids' => ['ids' => [1]]))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        /** @var \Doctrine\ORM\Query\Expr\Func $comp */
        $comp = $parts[0];
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Func', $parts[0]);
        self::assertEquals('root.id IN', $comp->getName());
        self::assertEquals(':ids', $comp->getArguments()[0]);
    }

    public function test_resolve_condition_with_root_in_name()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve('rootId = 1');
        self::assertEquals([
            ResolverInterface::T_AND => ['root.rootId = 1' => null],
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(array('root.id' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => ['root.id = ?' => 1],
        ], $result['expressions']);

        $result = $resolver->resolve(array('id' => 1, 'name' => 'tester'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1,
                'root.name = ?' => 'tester',
            ],
        ], $result['expressions']);

        $result = $resolver->resolve(array('id = ?' => 1, 'name = ?' => 'tester'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1,
                'root.name = ?' => 'tester',
            ],
        ], $result['expressions']);

        $result = $resolver->resolve(array('root.id and root.id IS NULL' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(array('root.id = ? and root.id IS NULL' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1,
                'root.id IS NULL' => null,
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_parameters_and_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(['id = ? and (id IS NULL or active = ?)' => [1, false]]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = ?' => 1,
                [
                    ResolverInterface::T_OR => [
                        'root.id IS NULL' => null,
                        'root.active = ?' => false,
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(['id = ? or (id IS NULL and active = ?)' => [1, false]]);
        self::assertEquals([
            ResolverInterface::T_OR => [
                'root.id = ?' => 1,
                [
                    ResolverInterface::T_AND => [
                        'root.id IS NULL' => null,
                        'root.active = ?' => false,
                    ]
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_named_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(array('id = :id' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => ['root.id = :id' => 1],
        ], $result['expressions']);

        $result = $resolver->resolve(array('id = :id' => ['id' => 1], 'name = :name' => ['name' => 'tester']));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                'root.name = :name' => 'tester',
            ],
        ], $result['expressions']);

        $result = $resolver->resolve(array('root.id = :id' => ['id' => 1], 'root.name = :name' => ['name' => 'tester']));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                'root.name = :name' => 'tester',
            ],
        ], $result['expressions']);

        $result = $resolver->resolve(array('root.id = :id' => ['id' => 1], 'root.name IS NULL'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                'root.name IS NULL' => null,
            ],
        ], $result['expressions']);

        $result = $resolver->resolve(array('root.id = :id and name IS NULL' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                'root.name IS NULL' => null,
            ],
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_named_parameters_and_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(['id = :id and (id IS NULL or active = :active)' => ['id' => 1, 'active' => false]]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                [
                    ResolverInterface::T_OR => [
                        'root.id IS NULL' => null,
                        'root.active = :active' => false,
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(['id = :id or (id IS NULL and active = :active)' => ['id' => 1, 'active' => false]]);
        self::assertEquals([
            ResolverInterface::T_OR => [
                'root.id = :id' => 1,
                [
                    ResolverInterface::T_AND => [
                        'root.id IS NULL' => null,
                        'root.active = :active' => false,
                    ]
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_mixed_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolve(['id = :id and (id IS NULL or active = ?)' => ['id' => 1, false]]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                'root.id = :id' => 1,
                [
                    ResolverInterface::T_OR => [
                        'root.id IS NULL' => null,
                        'root.active = :active' => false,
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolve(['id or (id IS NULL and active = :active)' => [1, 'active' => false]]);
        self::assertEquals([
            ResolverInterface::T_OR => [
                'root.id = ?' => 1,
                [
                    ResolverInterface::T_AND => [
                        'root.id IS NULL' => null,
                        'root.active = :active' => false,
                    ]
                ]
            ]
        ], $result['expressions']);
    }
}

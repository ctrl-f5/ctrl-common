<?php

namespace Ctrl\Common\Test\Criteria;

use Ctrl\Common\Criteria\Resolver;
use Ctrl\Common\Criteria\ResolverInterface;
use Ctrl\Common\Test\EntityService\Criteria\Mock\QueryBuilder;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $rootAlias
     * @return Resolver
     */
    protected function getResolver($rootAlias)
    {
        return new Resolver($rootAlias);
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
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => 'test']
            ]
        ], $result);

        $result = $resolver->createGraph('test and (test2 or test3)');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => 'test'],
                [ResolverInterface::T_OR => [
                    [ResolverInterface::T_EXPR => 'test2'],
                    [ResolverInterface::T_EXPR => 'test3'],
                ]]
            ],
        ], $result);

        $result = $resolver->createGraph('((test)) and (((test2) or (test3)))');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => 'test'],
                [ResolverInterface::T_OR => [
                    [ResolverInterface::T_EXPR => 'test2'],
                    [ResolverInterface::T_EXPR => 'test3'],
                ]]
            ],
        ], $result);

        $result = $resolver->createGraph('test and test');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => 'test'],
                [ResolverInterface::T_EXPR => 'test'],
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

        $result = $resolver->resolveCriteria('id = 1');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]]
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria('id = 1 and id IS NULL');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria('id = 1 or id IS NULL');
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria('root.id = 1 and root.id IS NULL');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);
    }

    public function test_resolve_condition_with_root_in_name()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria('rootId = 1');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.rootId = 1' => null]],
            ]
        ], $result['expressions']);
    }

    public function test_resolve_string_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria('root.id = 1 and (id IS NULL or root.active = false)');
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [
                    ResolverInterface::T_OR => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = false' => null]],
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria('root.id = 1 or (id IS NULL and root.active = false)');
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [
                    ResolverInterface::T_AND => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = false' => null]],
                    ]
                ]
            ]
        ], $result['expressions']);

        $expected = [
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [
                    ResolverInterface::T_OR => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = false' => null]],
                    ]
                ],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                [ResolverInterface::T_EXPR => ['root.active = false' => null]],
            ]
        ];

        $result = $resolver->resolveCriteria('root.id = 1 and (id IS NULL or root.active = false) and (id IS NULL and root.active = false)');
        self::assertEquals($expected, $result['expressions']);
        $result = $resolver->resolveCriteria('root.id = 1 and (id IS NULL or root.active = false) and id IS NULL and root.active = false');
        self::assertEquals($expected, $result['expressions']);

        $result = $resolver->resolveCriteria('id = 1 or test = true or (id IS NULL and active IS NULL)', ResolverInterface::T_OR);
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.test = true' => null]],
                [
                    ResolverInterface::T_AND => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active IS NULL' => null]],
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria('id = 1 or test = true or (id IS NULL or active IS NULL)', ResolverInterface::T_OR);
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.test = true' => null]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                [ResolverInterface::T_EXPR => ['root.active IS NULL' => null]],
            ]
        ], $result['expressions']);
    }

    public function test_resolve_array()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(array('id = 1', 'id IS NULL'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id = 1', 'test = 1 or tester = 2'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = 1' => null]],
                [ResolverInterface::T_OR => [
                    [ResolverInterface::T_EXPR => ['root.test = 1' => null]],
                    [ResolverInterface::T_EXPR => ['root.tester = 2' => null]],
                ]]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_array_with_values()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(array('id' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id and id' => [1, 2]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.id = ?' => 2]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id =' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id = :id' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(['root.id in' => ['test']]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id IN ?' => 'test']]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(array('root.id' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id' => 1, 'name' => 'tester'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.name = ?' => 'tester']],
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id = ?' => 1, 'name = ?' => 'tester'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.name = ?' => 'tester']],
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('root.id and root.id IS NULL' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('root.id = ? and root.id IS NULL' => 1));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_parameters_and_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(['id = ? and (id IS NULL or active = ?)' => [1, false]]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [
                    ResolverInterface::T_OR => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = ?' => false]],
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(['id = ? or (id IS NULL and active = ?)' => [1, false]]);
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [
                    ResolverInterface::T_AND => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = ?' => false]],
                    ]
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_named_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(array('id = :id' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]]
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('id = :id' => ['id' => 1], 'name = :name' => ['name' => 'tester']));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [ResolverInterface::T_EXPR => ['root.name = :name' => 'tester']],
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('root.id = :id' => ['id' => 1], 'root.name = :name' => ['name' => 'tester']));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [ResolverInterface::T_EXPR => ['root.name = :name' => 'tester']],
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('root.id = :id' => ['id' => 1], 'root.name IS NULL'));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [ResolverInterface::T_EXPR => ['root.name IS NULL' => null]],
            ],
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(array('root.id = :id and name IS NULL' => ['id' => 1]));
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [ResolverInterface::T_EXPR => ['root.name IS NULL' => null]],
            ],
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_named_parameters_and_braces()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(['id = :id and (id IS NULL or active = :active)' => ['id' => 1, 'active' => false]]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [
                    ResolverInterface::T_OR => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = :active' => false]],
                    ]
                ]
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria(['id = :id or (id IS NULL and active = :active)' => ['id' => 1, 'active' => false]]);
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [
                    ResolverInterface::T_AND => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = :active' => false]],
                    ]
                ]
            ]
        ], $result['expressions']);
    }

    public function test_resolve_conditions_with_mixed_parameters()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria([
            'id = :id and (id IS NULL or active = ?)' => ['id' => 1, false]
        ]);
        self::assertEquals([
            ResolverInterface::T_AND => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [
                    ResolverInterface::T_OR => [
                        [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                        [ResolverInterface::T_EXPR => ['root.active = ?' => false]],
                    ],
                ],
            ]
        ], $result['expressions']);

        $result = $resolver->resolveCriteria([
            'id = :id' => ['id' => 1],
            'test1 = 1 and test2 = 2',
        ], Resolver::T_OR);
        self::assertEquals([
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = :id' => 1]],
                [ResolverInterface::T_AND => [
                    [ResolverInterface::T_EXPR => ['root.test1 = 1' => null]],
                    [ResolverInterface::T_EXPR => ['root.test2 = 2' => false]],
                ]],
            ]
        ], $result['expressions']);

        $expected = [
            ResolverInterface::T_OR => [
                [ResolverInterface::T_EXPR => ['root.id = ?' => 1]],
                [ResolverInterface::T_EXPR => ['root.id = ?' => 2]],
                [ResolverInterface::T_EXPR => ['root.id IS NULL' => null]],
                [ResolverInterface::T_EXPR => ['root.active = :active' => false]],
                [ResolverInterface::T_EXPR => ['root.test = 1' => null]],
                [ResolverInterface::T_EXPR => ['root.test2 = 2' => false]],
                [ResolverInterface::T_AND => [
                    [ResolverInterface::T_EXPR => ['root.test = 1' => null]],
                    [ResolverInterface::T_EXPR => ['root.test2 = 2' => false]],
                ]],
            ]
        ];

        $result = $resolver->resolveCriteria([
            'id' => 1,
            'id = ?' => 2,
            'id IS NULL or active = :active' => ['active' => false],
            'test = 1 or test2 = 2',
            'test = 1 and test2 = 2',
        ], Resolver::T_OR);
        self::assertEquals($expected, $result['expressions']);

        $result = $resolver->resolveCriteria([
            'id = ? or id = ? or (id IS NULL or active = :active) or (test = 1 or test2 = 2) or (test = 1 and test2 = 2)' =>
                [1, 2, 'active' => false]
        ]);
        self::assertEquals($expected, $result['expressions']);
    }

    public function test_resolve_joins()
    {
        $resolver = $this->getResolver('root');

        $result = $resolver->resolveCriteria(['id' => 1]);
        self::assertEquals([], $result['joins']);

        $result = $resolver->resolveCriteria(['users.name' => 'test']);
        self::assertEquals([
            'root.users' => 'users'
        ], $result['joins']);

        $result = $resolver->resolveCriteria(['users.address.street' => 'test']);
        self::assertEquals([
            'root.users' => 'users',
            'users.address' => 'address',
        ], $result['joins']);
    }
}

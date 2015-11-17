<?php

namespace Ctrl\Common\Test\Criteria\Adapter;

use Ctrl\Common\Criteria\Adapter\DoctrineAdapter;
use Ctrl\Common\Criteria\Resolver;
use Ctrl\Common\Test\EntityService\Criteria\Mock\QueryBuilder;

class DoctrineAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAdapter
     */
    protected $adapter;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @param $rootAlias
     * @return DoctrineAdapter
     */
    protected function getAdapter($rootAlias)
    {
        if (!$this->adapter) {
            $this->resolver = new Resolver($rootAlias);
            $this->adapter = new DoctrineAdapter($this->resolver);
        }

        return $this->adapter;
    }

    /**
     * @param \Doctrine\ORM\Query\Expr\Comparison $expr
     * @param string $left
     * @param string $operator
     * @param string $right
     */
    public static function assertQueryExpressionIsComparison($expr, $left, $operator, $right)
    {
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Comparison', $expr);
        self::assertEquals($left, $expr->getLeftExpr());
        self::assertEquals($operator, $expr->getOperator());
        self::assertEquals($right, $expr->getRightExpr());
    }

    /**
     * @param \Doctrine\ORM\Query\Expr\Andx $expr
     * @param int $count
     */
    public static function assertQueryExpressionIsAndx($expr, $count)
    {
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Andx', $expr);
        self::assertSame($count, count($expr->getParts()));
    }

    /**
     * @param \Doctrine\ORM\Query\Expr\Orx $expr
     * @param int $count
     */
    public static function assertQueryExpressionIsOrx($expr, $count)
    {
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Orx', $expr);
        self::assertSame($count, count($expr->getParts()));
    }

    public function test_create_query_expression_from_string()
    {
        $adapter = $this->getAdapter('root');

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id = 1'));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertSame('root.id = 1', $parts[0]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id = true'));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertSame('root.id = true', $parts[0]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id IS NULL'));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertSame('root.id IS NULL', $parts[0]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id IS NOT NULL'));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertSame('root.id IS NOT NULL', $parts[0]);
    }

    public function test_create_query_expression_from_string_multiple()
    {
        $adapter = $this->getAdapter('root');

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id = 1 AND id = 2'));
        self::assertQueryExpressionIsAndx($expr, 2);
        $parts = $expr->getParts();
        self::assertSame('root.id = 1', $parts[0]);
        self::assertSame('root.id = 2', $parts[1]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression('id = true OR id = 1'));
        self::assertQueryExpressionIsOrx($expr, 2);
        $parts = $expr->getParts();
        self::assertSame('root.id = true', $parts[0]);
        self::assertSame('root.id = 1', $parts[1]);
    }

    public function test_create_query_expression_from_array()
    {
        $adapter = $this->getAdapter('root');

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression(array('id = 1', 'id = 2')));
        self::assertQueryExpressionIsAndx($expr, 2);
        $parts = $expr->getParts();
        self::assertSame('root.id = 1', $parts[0]);
        self::assertSame('root.id = 2', $parts[1]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression(array('id = :id' => ['id' => 1])));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertQueryExpressionIsComparison($parts[0], 'root.id', '=', ':id');

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolveExpression(array('id IS NULL')));
        self::assertQueryExpressionIsAndx($expr, 1);
        $parts = $expr->getParts();
        self::assertSame('root.id IS NULL', $parts[0]);

        $queryBuilder = new QueryBuilder();
        $expr = $adapter->createQueryExpression($queryBuilder, $this->resolver->resolveCriteria(array('id IN :ids' => ['ids' => [1]]))['expressions']);
        $parts = $expr->getParts();
        self::assertEquals(1, count($parts));
        /** @var \Doctrine\ORM\Query\Expr\Func $comp */
        $comp = $parts[0];
        self::assertInstanceOf('Doctrine\ORM\Query\Expr\Func', $parts[0]);
        self::assertEquals('root.id IN', $comp->getName());
        self::assertEquals(':ids', $comp->getArguments()[0]);
    }

    protected function resolveExpression($expression)
    {
        $result = $this->resolver->resolveCriteria($expression);
        return $result['expressions'];
    }
}

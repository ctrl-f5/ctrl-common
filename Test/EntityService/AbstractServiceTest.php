<?php

namespace Ctrl\Common\Test\EntityService;

use Ctrl\Common\EntityService\AbstractDoctrineService;

class AbstractDoctrineServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractDoctrineService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = $this->getMockBuilder('Ctrl\\Common\\EntityService\\AbstractDoctrineService')
            ->disableOriginalConstructor()
            ->setMethods(array())
            ->getMockForAbstractClass()
        ;
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_get_root_alias()
    {
        $this->service->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue('\\Namespace\\MyTestClass'));

        $this->assertEquals("myTestClass", $this->service->getRootAlias());
    }

    public function test_assert_entity_instance_accepts_correct_class()
    {
        $this->service->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue('stdClass'));

        $entity = new \stdClass();

        $this->assertNull($this->service->assertEntityInstance($entity));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_assert_entity_instance_rejects_incorrect_class()
    {
        $this->service->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue('Ctrl\\Common\\Entity\\User'));

        $entity = new \stdClass();

        $this->assertNull($this->service->assertEntityInstance($entity));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_assert_entity_instance_rejects_non_object()
    {
        $this->service->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue('Ctrl\\RadBundle\\Entity\\User'));

        $entity = array('id' => 1);

        $this->assertNull($this->service->assertEntityInstance($entity));
    }
}

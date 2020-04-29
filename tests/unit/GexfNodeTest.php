<?php

use Codeception\Test\Unit;
use tsn\GexfNode;

class GexfNodeTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var \tsn\GexfNode */
    private $GexfNode;

    /**
     * Test addition, existence and retrieval of child Node
     */
    public function testChildNode()
    {

        // Create Object
        $childNode = new GexfNode('childNode');

        // Shouldn't exist
        $this->tester->assertFalse($this->GexfNode->childExists($childNode));

        // Put it there
        $this->GexfNode->addChildNode($childNode);

        // Should exist
        $this->tester->assertTrue($this->GexfNode->childExists($childNode));

        // Also shouldn't be empty because it exists
        $this->tester->assertNotEmpty($this->GexfNode->getChildNodes());
    }

    /**
     * Test addition and retrieval of coordinates
     */
    public function testCoordinates()
    {
        // Should be empty
        $this->tester->assertEmpty($this->GexfNode->getCoordinates());

        // Set them
        $this->GexfNode->setCoordinates(1.1, 2.2, 3.3);

        // Should be there now
        $this->tester->assertNotEmpty($this->GexfNode->getCoordinates());

        // Should be what we set
        $this->tester->assertEquals(['x' => 1.1, 'y' => 2.2, 'z' => 3.3], $this->GexfNode->getCoordinates());

    }

    /**
     * Test addition, existence and retrieval of parent Node
     */
    public function testParentNode()
    {
        // Create Object
        $parentNode = new GexfNode('parentNode');

        // Shouldn't exist
        $this->tester->assertFalse($this->GexfNode->parentExists($parentNode));

        // Put it there
        $this->GexfNode->addParentNode($parentNode);

        // Should exist
        $this->tester->assertTrue($this->GexfNode->parentExists($parentNode));

        // Also shouldn't be empty because it exists
        $this->tester->assertNotEmpty($this->GexfNode->getParentNodes());

        // Add a second parent
        $stepParentNode = new GexfNode('stepParentNode');
        $this->GexfNode->addParentNode($stepParentNode);

        // Should be 2 now
        $this->tester->assertCount(2, $this->GexfNode->getParentNodes());
    }

    /**
     * Test direct properties set by the the __construct() method
     */
    public function testProperties()
    {
        // These were all set in the constructor
        $this->tester->assertNotEmpty($this->GexfNode->getId());
        $this->tester->assertEquals('neo', $this->GexfNode->getId());
        $this->tester->assertEquals('testNode', $this->GexfNode->getName());
        $this->tester->assertEquals('2020-03-16', $this->GexfNode->getStartDate());
        $this->tester->assertEquals('2020-04-30', $this->GexfNode->getEndDate());
    }

    /**
     * Test visualization properties
     * @throws \Exception
     */
    public function testVisualizationProperties()
    {
        // Test the Set
        $this->GexfNode->setSize(1.5);
        // Test the Get
        $this->tester->assertEquals(1.5, $this->GexfNode->getSize());

        // Roll through the shapes
        $availableShapes = [
            GexfNode::SHAPE_DIAMOND,
            GexfNode::SHAPE_DISC,
            GexfNode::SHAPE_IMAGE,
            GexfNode::SHAPE_SQUARE,
            GexfNode::SHAPE_TRIANGLE,
        ];

        foreach ($availableShapes as $shape) {
            $this->GexfNode->setShape($shape);
            $this->tester->assertEquals($shape, $this->GexfNode->getShape());
        }

        $this->tester->expectThrowable(Exception::class, function () {
            $this->GexfNode->setShape('star');
        });
    }

    protected function _after()
    {
        $this->GexfNode = null;
    }

    protected function _before()
    {
        // Start Date when I started COVID-19 Quarantine :(
        // End Date is the last "official" stay-at-home date... but I'm still gonna stay at home.
        $this->GexfNode = new GexfNode('testNode', 'neo', '2020-03-16', '2020-04-30');
    }
}

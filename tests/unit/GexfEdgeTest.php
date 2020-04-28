<?php

use Codeception\Test\Unit;
use tsn\GexfEdge;
use tsn\GexfNode;

class GexfEdgeTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @var \tsn\GexfEdge */
    private $GexfEdge;
    /** @var \tsn\GexfNode */
    private $SourceNode;
    /** @var \tsn\GexfNode */
    private $TargetNode;

    /**
     * Test the additional properties on the <edge> element that are not in constructor
     */
    public function testAdditionalProperties()
    {

        $this->tester->assertEmpty($this->GexfEdge->getKind());
        $this->tester->assertEmpty($this->GexfEdge->getLabel());

        $this->GexfEdge->setKind('test line');
        $this->GexfEdge->setLabel('Unit Test Edge');

        $this->tester->assertEquals('test line', $this->GexfEdge->getKind());
        $this->tester->assertEquals('Unit Test Edge', $this->GexfEdge->getLabel());

    }

    /**
     * Test the properties setup in the constructor
     */
    public function testProperties()
    {
        $this->tester->assertEquals($this->SourceNode->getId(), $this->GexfEdge->getSourceId());
        $this->tester->assertEquals($this->TargetNode->getId(), $this->GexfEdge->getTargetId());
        $this->tester->assertEquals(GexfEdge::TYPE_DIRECTED, $this->GexfEdge->getType());
        $this->tester->assertEquals('e-n-bf220428ba1c494935f0cf66680590bdn-8e65decc3598d3ebebcb735b16acf53f', $this->GexfEdge->getId());
        $this->tester->assertEquals('2020-03-16', $this->GexfEdge->getStartDate());
        $this->tester->assertEquals('2020-04-30', $this->GexfEdge->getEndDate());

        $this->tester->assertEquals(2, $this->GexfEdge->getWeight());
        $this->GexfEdge->addToWeight(3);
        $this->tester->assertEquals(5, $this->GexfEdge->getWeight());
    }

    /**
     * Test visualization properties
     * @throws \Exception
     */
    public function testVisualizationProperties()
    {
        $availableShapes = [GexfEdge::SHAPE_SOLID, GexfEdge::SHAPE_DASHED, GexfEdge::SHAPE_DOTTED, GexfEdge::SHAPE_DOUBLE];
        foreach ($availableShapes as $shape) {
            $this->GexfEdge->setShape($shape);
            $this->tester->assertEquals($shape, $this->GexfEdge->getShape());
        }

        $this->tester->expectThrowable(Exception::class, function () {
            $this->GexfEdge->setShape('curve');
        });

        $this->GexfEdge->setThickness(1.2);
        $this->tester->assertEquals(1.2, $this->GexfEdge->getThickness());
    }

    protected function _after()
    {
        $this->SourceNode = null;
        $this->TargetNode = null;
        $this->GexfEdge = null;
    }

    protected function _before()
    {
        $this->SourceNode = new GexfNode('sourceNode');
        $this->TargetNode = new GexfNode('targetNode');
        $this->GexfEdge = new GexfEdge($this->SourceNode, $this->TargetNode, 2, GexfEdge::TYPE_DIRECTED, '2020-03-16', '2020-04-30');
    }
}

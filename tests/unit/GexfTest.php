<?php

use Codeception\Test\Unit;
use tsn\Gexf;
use tsn\GexfAttribute;
use tsn\GexfEdge;
use tsn\GexfNode;
use tsn\GexfSpell;

class GexfTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var \tsn\Gexf */
    private $Gexf;

    /**
     * Test the construction of Attributes, and rendering
     * @throws \Exception
     */
    public function testAttributes()
    {
        $this->Gexf
            ->setTitle('A Web network')
            ->setEdgeType(GexfEdge::TYPE_DIRECTED);

        $urlAttribute = new GexfAttribute('url', null);
        $indegreeAttribute = new GexfAttribute('indegree', 0, GexfAttribute::TYPE_FLOAT);
        $frogAttribute = (new GexfAttribute('frog', null, GexfAttribute::TYPE_BOOLEAN))
            ->setDefaultValue('true');

        $categoryAttribute = (new GexfAttribute('category', null))
            ->asListStringType(['red', 'green', 'blue', 'yellow', 'clear'], 'clear');

        $node0 = (new GexfNode('Gephi'))
            ->addAttribute($urlAttribute->setValue('http://gephi.org'))
            ->addAttribute($indegreeAttribute->setValue(1));

        $node1 = (new GexfNode('Webatlas'))
            ->addAttribute($urlAttribute->setValue('http://webatlas.fr'))
            ->addAttribute($indegreeAttribute->setValue(2));

        $node2 = (new GexfNode('RTGI'))
            ->addAttribute($urlAttribute->setValue('http://rtgi.fr'))
            ->addAttribute($indegreeAttribute->setValue(1));

        $node3 = (new GexfNode('BarabasiLab'))
            ->addAttribute($urlAttribute->setValue('http://barabasilab.com'))
            ->addAttribute($indegreeAttribute->setValue(1))
            ->addAttribute($frogAttribute->setValue('false'));

        // Test that the value can be retrieved
        $this->tester->assertEquals('http://barabasilab.com', $node3->getAttributeValue('url'));

        $this->Gexf->addNode($node0);
        $this->Gexf->addNode($node1);
        $this->Gexf->addNode($node2);
        $this->Gexf->addNode($node3);

        $edge01 = (new GexfEdge($node0, $node1, 1, $this->Gexf->getDefaultEdgeType()))
            ->addAttribute($categoryAttribute->setValue('red'));

        $this->Gexf->addEdge($edge01);
        $this->Gexf->addEdge(new GexfEdge($node0, $node2, 1, $this->Gexf->getDefaultEdgeType()));
        $this->Gexf->addEdge(new GexfEdge($node1, $node0, 1, $this->Gexf->getDefaultEdgeType()));
        $this->Gexf->addEdge(new GexfEdge($node2, $node1, 1, $this->Gexf->getDefaultEdgeType()));
        $this->Gexf->addEdge(new GexfEdge($node0, $node3, 1, $this->Gexf->getDefaultEdgeType()));

        $this->Gexf->render();

        $this->tester->assertEquals($node0, $this->Gexf->getNode($node0));
        $this->tester->assertEquals($node1, $this->Gexf->getNode($node1));
        $this->tester->assertEquals($node2, $this->Gexf->getNode($node2));
        $this->tester->assertEquals($node3, $this->Gexf->getNode($node3));

        $this->tester->assertEquals(self::attributesOutput(), $this->Gexf->gexfFile);
    }

    /**
     * Tests the start/end times on elements
     */
    public function testDynamics()
    {
        $this->Gexf
            ->setTitle('A Web network changing over time')
            ->setMode(Gexf::MODE_DYNAMIC)
            ->setEdgeType(GexfEdge::TYPE_DIRECTED)
            ->setStartEndDate('2009-01-01', '2009-03-20');

        // Static Attributes
        $urlAttribute = new GexfAttribute('url', null);
        $frogAttribute = (new GexfAttribute('frog', null, GexfAttribute::TYPE_BOOLEAN))
            ->setDefaultValue('true');

        // Dynamic Attribute
        $indegreeAttribute = (new GexfAttribute('indegree', null, GexfAttribute::TYPE_FLOAT, Gexf::MODE_DYNAMIC));

        $node0a = (new GexfNode('ThePizzy.net', '2003-03-21'))
            ->addAttribute($urlAttribute->setValue('https://thepizzy.net'))
            ->addAttribute($indegreeAttribute->setValue(8));

        $node0 = (new GexfNode('Gephi', null, '2009-03-01'))
            ->addAttribute($urlAttribute->setValue('http://gephi.org'))
            ->addAttribute($indegreeAttribute->setValue(1))
            ->addChildNode($node0a)
            ->addSpell(new GexfSpell(null, '2009-03-01'))
            ->addSpell(new GexfSpell('2009-03-05', '2009-03-10'));

        $node1 = (new GexfNode('Webatlas'))
            ->addAttribute($urlAttribute->setValue('http://webatlas.fr'))
            ->addAttribute($indegreeAttribute->setValue(1)->setStartEndDate(null, '2009-03-01'))
            ->addAttribute($indegreeAttribute->setValue(2)->setStartEndDate('2009-03-01', '2009-03-10'))
            ->addAttribute($indegreeAttribute->setValue(1)->setStartEndDate('2009-03-11'));

        $node2 = (new GexfNode('RTGI', null, null, '2009-03-10'))
            ->addAttribute($urlAttribute->setValue('http://rtgi.fr'))
            ->addAttribute($indegreeAttribute->setValue(0)->setStartEndDate(null, '2009-03-01'))
            ->addAttribute($indegreeAttribute->setValue(1)->setStartEndDate('2009-03-01'))
            ->addParentNode($node0)
            ->addParentNode($node1);

        $node3 = (new GexfNode('BarabasiLab'))
            ->addAttribute($urlAttribute->setValue('http://barabasilab.com'))
            ->addAttribute($frogAttribute->setValue('false'))
            ->addAttribute($indegreeAttribute->setValue(0)->setStartEndDate(null, '2009-03-01'))
            ->addAttribute($indegreeAttribute->setValue(1)->setStartEndDate('2009-03-01'))
            ->addParentNode($node0);

        // Test that the value can be retrieved
        $this->tester->assertEquals(0, $node3->getAttributeValue('indegree', null, '2009-03-01'));
        $this->tester->assertEquals(1, $node3->getAttributeValue('indegree', '2009-03-01'));

        $this->Gexf->addNode($node0);
        $this->Gexf->addNode($node1);
        $this->Gexf->addNode($node2);
        $this->Gexf->addNode($node3);

        // Put some spells to chop up this edge duration
        $edge02 = (new GexfEdge($node0, $node2, 1, $this->Gexf->getDefaultEdgeType(), '2009-03-01', '2009-03-10'))
            ->setType(GexfEdge::TYPE_UNDIRECTED)
            ->addSpell(new GexfSpell('2009-03-01', '2009-03-04'))
            ->addSpell(new GexfSpell('2009-03-06', '2009-03-10'));

        $this->Gexf->addEdge(new GexfEdge($node0, $node1, 1, $this->Gexf->getDefaultEdgeType(), '2009-03-01'));
        $this->Gexf->addEdge($edge02);
        $this->Gexf->addEdge(new GexfEdge($node1, $node0, 1, $this->Gexf->getDefaultEdgeType(), '2009-03-01'));
        $this->Gexf->addEdge(new GexfEdge($node2, $node1, 1, $this->Gexf->getDefaultEdgeType(), null, '2009-03-10'));
        $this->Gexf->addEdge(new GexfEdge($node0, $node3, 1, $this->Gexf->getDefaultEdgeType(), '2009-03-01'));

        $this->Gexf->render();

        $this->tester->assertEquals($node0, $this->Gexf->getNode($node0));
        $this->tester->assertEquals($node1, $this->Gexf->getNode($node1));
        $this->tester->assertEquals($node2, $this->Gexf->getNode($node2));
        $this->tester->assertEquals($node3, $this->Gexf->getNode($node3));

        $this->tester->assertEquals(self::dynamicOutput(), $this->Gexf->gexfFile);
    }

    /**
     * Test the Title, Keywords, and render of the meta tag
     */
    public function testHeaderSetup()
    {
        // Test wrong time format
        $this->tester->expectThrowable(Exception::class, function () {
            $this->Gexf->setTimeFormat('unix');
        });

        // Test wrong mode
        $this->tester->expectThrowable(Exception::class, function () {
            $this->Gexf->setMode('uncontrolled');
        });

        // Test wrong Edge Type
        $this->tester->expectThrowable(Exception::class, function () {
            $this->Gexf->setEdgeType('random');
        });

        $this->Gexf
            ->setTitle('A hello world! file')
            ->addKeywords(['basic', 'web'])
            ->render();

        // Test getting wrong type of Node Attribute Objects
        $this->tester->expectThrowable(Exception::class, function () {
            $this->getModule('Helpers\Unit')->invokeMethod($this->Gexf, 'getNodeAttributeObjects', ['unbound']);
        });

        $this->tester->assertEquals(self::headerOutput(), $this->Gexf->gexfFile);
    }

    /**
     * Test the addition of Nodes and Edges into the graph
     */
    public function testSimpleGraph()
    {
        $this->Gexf
            ->setTitle('A hello world! file')
            ->setEdgeType(GexfEdge::TYPE_DIRECTED);

        $node0 = (new GexfNode('Hello'))
            ->setCoordinates(15.783598, 40.109245, 3.4)
            ->setColor('#003366', 0.9)
            ->setSize(1.5)
            ->setShape(GexfNode::SHAPE_TRIANGLE);
        $node1 = (new GexfNode('World'));

        $edge0 = (new GexfEdge($node0, $node1, 1, $this->Gexf->getDefaultEdgeType()))
            ->setColor('587eaa', 1.0)
            ->setThickness(3.2)
            ->setShape(GexfEdge::SHAPE_DOTTED);

        $this->Gexf->addNode($node0);
        $this->Gexf->addNode($node1);

        $this->Gexf->addEdge($edge0);
        // Add this edge twice, to increment the weight
        $this->Gexf->addEdge($edge0);

        $this->Gexf->render();

        $this->tester->assertEquals($node0, $this->Gexf->getNode($node0));
        $this->tester->assertEquals($node1, $this->Gexf->getNode($node1));
        $this->tester->assertEquals(self::simpleGraphOutput(), $this->Gexf->gexfFile);
    }

    /**
     * Clear out the object
     */
    protected function _after()
    {
        $this->Gexf = null;
    }

    /**
     * Setup an object with common basic settings for tests
     */
    protected function _before()
    {
        $this->Gexf = (new Gexf('Test Title'))
            ->setTimeFormat(Gexf::TIMEFORMAT_DATE)
            ->setLastModifiedDate('2009-03-20')
            ->setCreator('Gephi.org');
    }

    /**
     * @used-by \GexfTest::testAttributes()
     * @return string
     */
    private static function attributesOutput()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <meta lastmodifieddate="2009-03-20"><creator>Gephi.org</creator><description>A Web network</description></meta>
    <graph defaultedgetype="directed" mode="static" timeformat="date"><attributes class="node" mode="static"><attribute id="a-572d4e421e5e6b9bc11d815e8a027112" title="url" type="string"/><attribute id="a-90e7c2f18dab8823e9d455879006367a" title="indegree" type="float"/><attribute id="a-938c2cc0dcc05f2b68c4287040cfcf71" title="frog" type="boolean"><default>true</default></attribute></attributes><attributes class="edge"><attribute id="a-c4ef352f74e502ef5e7bc98e6f4e493d" title="category" type="liststring"><default>clear</default><options>red,green,blue,yellow,clear</options></attribute></attributes><nodes><node id="n-198e1746eaaf235d823038597e3c6de1" label="Gephi"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://gephi.org"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1"/></attvalues></node><node id="n-2e4a37c451cce2ea34eb4c3eb760235d" label="Webatlas"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://webatlas.fr"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="2"/></attvalues></node><node id="n-07a74fe76b58df4c9e4b57f28836ddd6" label="RTGI"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://rtgi.fr"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1"/></attvalues></node><node id="n-e19a6d7fa3405edb12809ce76dfe0ea4" label="BarabasiLab"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://barabasilab.com"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1"/><attvalue for="a-938c2cc0dcc05f2b68c4287040cfcf71" value="false"/></attvalues></node></nodes><edges><edge id="e-n-198e1746eaaf235d823038597e3c6de1n-2e4a37c451cce2ea34eb4c3eb760235d" source="n-198e1746eaaf235d823038597e3c6de1" target="n-2e4a37c451cce2ea34eb4c3eb760235d" weight="1" type="directed"><viz:thickness value="1"/><viz:shape value="solid"/><attvalues><attvalue for="a-c4ef352f74e502ef5e7bc98e6f4e493d" value="red"/></attvalues></edge><edge id="e-n-198e1746eaaf235d823038597e3c6de1n-07a74fe76b58df4c9e4b57f28836ddd6" source="n-198e1746eaaf235d823038597e3c6de1" target="n-07a74fe76b58df4c9e4b57f28836ddd6" weight="1" type="directed"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-2e4a37c451cce2ea34eb4c3eb760235dn-198e1746eaaf235d823038597e3c6de1" source="n-2e4a37c451cce2ea34eb4c3eb760235d" target="n-198e1746eaaf235d823038597e3c6de1" weight="1" type="directed"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-07a74fe76b58df4c9e4b57f28836ddd6n-2e4a37c451cce2ea34eb4c3eb760235d" source="n-07a74fe76b58df4c9e4b57f28836ddd6" target="n-2e4a37c451cce2ea34eb4c3eb760235d" weight="1" type="directed"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-198e1746eaaf235d823038597e3c6de1n-e19a6d7fa3405edb12809ce76dfe0ea4" source="n-198e1746eaaf235d823038597e3c6de1" target="n-e19a6d7fa3405edb12809ce76dfe0ea4" weight="1" type="directed"><viz:thickness value="1"/><viz:shape value="solid"/></edge></edges></graph>
</gexf>';
    }

    /**
     * @used-by \GexfTest::testDynamics()
     * @return string
     */
    private static function dynamicOutput()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <meta lastmodifieddate="2009-03-20"><creator>Gephi.org</creator><description>A Web network changing over time</description></meta>
    <graph defaultedgetype="directed" mode="dynamic" timeformat="date" start="2009-01-01" end="2009-03-20"><attributes class="node" mode="static"><attribute id="a-572d4e421e5e6b9bc11d815e8a027112" title="url" type="string"/><attribute id="a-938c2cc0dcc05f2b68c4287040cfcf71" title="frog" type="boolean"><default>true</default></attribute></attributes><attributes class="node" mode="dynamic"><attribute id="a-90e7c2f18dab8823e9d455879006367a" title="indegree" type="float"/></attributes><nodes><node id="n-198e1746eaaf235d823038597e3c6de1" label="Gephi" start="2009-03-01"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://gephi.org"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1"/></attvalues><spells><spell end="2009-03-01"/><spell start="2009-03-05" end="2009-03-10"/></spells><nodes><node id="2003-03-210b239a0edcfb68bad9b0b671c725b4e9" label="ThePizzy.net"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="https://thepizzy.net"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="8"/></attvalues></node></nodes></node><node id="n-2e4a37c451cce2ea34eb4c3eb760235d" label="Webatlas"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://webatlas.fr"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1" end="2009-03-01"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="2" start="2009-03-01" end="2009-03-10"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1" start="2009-03-11"/></attvalues></node><node id="n-07a74fe76b58df4c9e4b57f28836ddd6" label="RTGI" end="2009-03-10"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://rtgi.fr"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="0" end="2009-03-01"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1" start="2009-03-01"/></attvalues><parents><parent for="n-198e1746eaaf235d823038597e3c6de1"/><parent for="n-2e4a37c451cce2ea34eb4c3eb760235d"/></parents></node><node id="n-e19a6d7fa3405edb12809ce76dfe0ea4" label="BarabasiLab" pid="n-198e1746eaaf235d823038597e3c6de1"><viz:size value="1"/><viz:shape value="disc"/><attvalues><attvalue for="a-572d4e421e5e6b9bc11d815e8a027112" value="http://barabasilab.com"/><attvalue for="a-938c2cc0dcc05f2b68c4287040cfcf71" value="false"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="0" end="2009-03-01"/><attvalue for="a-90e7c2f18dab8823e9d455879006367a" value="1" start="2009-03-01"/></attvalues></node></nodes><edges><edge id="e-n-198e1746eaaf235d823038597e3c6de1n-2e4a37c451cce2ea34eb4c3eb760235d" source="n-198e1746eaaf235d823038597e3c6de1" target="n-2e4a37c451cce2ea34eb4c3eb760235d" weight="1" type="directed" start="2009-03-01"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-07a74fe76b58df4c9e4b57f28836ddd6n-198e1746eaaf235d823038597e3c6de1" source="n-198e1746eaaf235d823038597e3c6de1" target="n-07a74fe76b58df4c9e4b57f28836ddd6" weight="1" type="undirected" start="2009-03-01" end="2009-03-10"><viz:thickness value="1"/><viz:shape value="solid"/><spells><spell start="2009-03-01" end="2009-03-04"/><spell start="2009-03-06" end="2009-03-10"/></spells></edge><edge id="e-n-2e4a37c451cce2ea34eb4c3eb760235dn-198e1746eaaf235d823038597e3c6de1" source="n-2e4a37c451cce2ea34eb4c3eb760235d" target="n-198e1746eaaf235d823038597e3c6de1" weight="1" type="directed" start="2009-03-01"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-07a74fe76b58df4c9e4b57f28836ddd6n-2e4a37c451cce2ea34eb4c3eb760235d" source="n-07a74fe76b58df4c9e4b57f28836ddd6" target="n-2e4a37c451cce2ea34eb4c3eb760235d" weight="1" type="directed" end="2009-03-10"><viz:thickness value="1"/><viz:shape value="solid"/></edge><edge id="e-n-198e1746eaaf235d823038597e3c6de1n-e19a6d7fa3405edb12809ce76dfe0ea4" source="n-198e1746eaaf235d823038597e3c6de1" target="n-e19a6d7fa3405edb12809ce76dfe0ea4" weight="1" type="directed" start="2009-03-01"><viz:thickness value="1"/><viz:shape value="solid"/></edge></edges></graph>
</gexf>';
    }

    /**
     * @used-by \GexfTest::testHeaderSetup()
     * @return string
     */
    private static function headerOutput()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <meta lastmodifieddate="2009-03-20"><creator>Gephi.org</creator><description>A hello world! file</description><keywords>basic, web</keywords></meta>
    <graph defaultedgetype="undirected" mode="static" timeformat="date"></graph>
</gexf>';
    }

    /**
     * @used-by \GexfTest::testSimpleGraph()
     * @return string
     */
    private static function simpleGraphOutput()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <meta lastmodifieddate="2009-03-20"><creator>Gephi.org</creator><description>A hello world! file</description></meta>
    <graph defaultedgetype="directed" mode="static" timeformat="date"><nodes><node id="n-8b1a9953c4611296a827abf8c47804d7" label="Hello"><viz:color hex="003366" a="0.9"/><viz:position x="15.783598" y="40.109245" z="3.4"/><viz:size value="1.5"/><viz:shape value="triangle"/></node><node id="n-f5a7924e621e84c9280a9a27e1bcb7f6" label="World"><viz:size value="1"/><viz:shape value="disc"/></node></nodes><edges><edge id="e-n-8b1a9953c4611296a827abf8c47804d7n-f5a7924e621e84c9280a9a27e1bcb7f6" source="n-8b1a9953c4611296a827abf8c47804d7" target="n-f5a7924e621e84c9280a9a27e1bcb7f6" weight="2" type="directed"><viz:color hex="587eaa" a="1"/><viz:thickness value="3.2"/><viz:shape value="dotted"/></edge></edges></graph>
</gexf>';
    }
}

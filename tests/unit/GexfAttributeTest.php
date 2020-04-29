<?php

use Codeception\Test\Unit;
use tsn\Gexf;
use tsn\GexfAttribute;

class GexfAttributeTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var \tsn\GexfAttribute */
    private $GexfAttribute;

    /**
     * Test the ListString Type functionality
     */
    public function testListString()
    {
        // Set initial options
        $expectedOptions = ['1.1', '2.2', '3.3', '4.4'];
        // Put in an intentional duplicate, as a string with a pipe
        $duplicatedOptions = '1.1|2.2|3.3|4.4|3.3';

        $this->GexfAttribute->asListStringType($duplicatedOptions, 2.2);

        $this->tester->assertEquals(2.2, $this->GexfAttribute->getDefaultValue());
        $this->tester->assertEquals($expectedOptions, $this->GexfAttribute->getListStringOptions());

        // Test semi-colon processing & spaces
        $duplicatedOptions = '1.1;    2.2; 3.3;  4.4; 3.3';
        $this->GexfAttribute->asListStringType($duplicatedOptions, 2.2);
        $this->tester->assertEquals($expectedOptions, $this->GexfAttribute->getListStringOptions());

        // Test comma processing
        $duplicatedOptions = '1.1, 2.2, 3.3, 4.4, 3.3';
        $this->GexfAttribute->asListStringType($duplicatedOptions, 2.2);
        $this->tester->assertEquals($expectedOptions, $this->GexfAttribute->getListStringOptions());

        // Test adding an option to the set
        $this->GexfAttribute->addListStringOptions('5.5');
        $expectedOptions[] = '5.5';
        $this->tester->assertEquals($expectedOptions, $this->GexfAttribute->getListStringOptions());

        // List String as CSV
        $this->GexfAttribute->setValue('1.1,2.2');
        $this->tester->assertEquals('1.1,2.2', $this->GexfAttribute->getValue());

        // List String as Array
        $this->GexfAttribute->setValue(['1.1', '2.2']);
        $this->tester->assertEquals('1.1,2.2', $this->GexfAttribute->getValue());

        $this->tester->expectThrowable(Exception::class, function () {
            $this->GexfAttribute->setDefaultValue('1.5');
        });

    }

    /**
     * Test properties set in the constructor
     */
    public function testProperties()
    {
        $this->tester->assertEquals('testAttribute', $this->GexfAttribute->getName());
        $this->tester->assertEquals('attributeId', $this->GexfAttribute->getId());
        $this->tester->assertEquals(1.1, $this->GexfAttribute->getValue());
        $this->tester->assertEquals(GexfAttribute::TYPE_FLOAT, $this->GexfAttribute->getType());
        $this->tester->assertEquals(Gexf::MODE_DYNAMIC, $this->GexfAttribute->getMode());
        $this->tester->assertEquals('2020-03-16', $this->GexfAttribute->getStartDate());
        $this->tester->assertEquals('2020-04-30', $this->GexfAttribute->getEndDate());
        $this->tester->assertEquals('av-7d3db26432620842963a916fc08ee2ab', $this->GexfAttribute->getKey());

        $this->tester->expectThrowable(Exception::class, function () {
            $this->GexfAttribute->setType('MongoBinData');
        });

        $this->tester->expectThrowable(Exception::class, function () {
            $this->GexfAttribute->setMode('random');
        });
    }

    protected function _after()
    {
        $this->GexfAttribute = null;
    }

    protected function _before()
    {
        $this->GexfAttribute = new GexfAttribute('testAttribute', 1.1, GexfAttribute::TYPE_FLOAT, 'attributeId', Gexf::MODE_DYNAMIC, '2020-03-16', '2020-04-30');
    }
}

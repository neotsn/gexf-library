<?php

use Codeception\Test\Unit;
use tsn\GexfSpell;

class GexfSpellTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var tsn\GexfSpell */
    private $GexfSpell;

    /**
     * Test the Date methods
     */
    public function testStartEndDates()
    {
        // Test that the dates are correct as constructed
        $this->tester->assertEquals('2020-03-16', $this->GexfSpell->getStartDate());
        $this->tester->assertEquals('2020-04-30', $this->GexfSpell->getEndDate());

        // Test the / format for dates
        $this->tester->expectThrowable(Exception::class, function() {
            $this->GexfSpell->setStartEndDate('2020/03/16');
        });

        // Test the MM-DD-YY format for dates
        $this->tester->expectThrowable(Exception::class, function() {
            $this->GexfSpell->setStartEndDate(null, '04-30-2020');
        });
    }

    /**
     * Test the ID method
     */
    public function testProperties() {
        $this->tester->assertEquals('2020-03-16-2020-04-30', $this->GexfSpell->getId());
    }

    protected function _after()
    {
        $this->GexfSpell = null;
    }

    protected function _before()
    {
        $this->GexfSpell = new GexfSpell('2020-03-16', '2020-04-30');
    }
}

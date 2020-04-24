<?php

namespace tsn;

use tsn\Traits\GexfDates;

/**
 * Class GexfSpell
 * Represents the time during which an Edge lives
 * @package tsn
 */
class GexfSpell
{
    use GexfDates;

    /**  @var string */
    private $id;

    /**
     * GexfSpell constructor.
     *
     * @param $startDate
     * @param $endDate
     *
     * @throws \Exception
     */
    public function __construct($startDate, $endDate)
    {
        $this
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            // After start/end defined
            ->setSpellId();
    }

    /**
     * @return mixed
     */
    public function getSpellId()
    {
        return $this->id;
    }

    /**
     * Generate the <spell> XML Tag string for use in the <spells> tag
     * @return string
     */
    public function render()
    {
        return '<spell' . $this->renderStartEndDates() . ' />';
    }

    /**
     * Set the spell id
     * @return \tsn\GexfSpell
     */
    public function setSpellId()
    {
        $this->id = $this->getStartDate() . "-" . $this->getEndDate();

        return $this;
    }
}

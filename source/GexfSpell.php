<?php

namespace tsn;

use tsn\traits\GexfDates;

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
            ->setId();
    }

    /**
     * @return mixed
     */
    public function getId()
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
    public function setId()
    {
        $this->id = $this->getStartDate() . "-" . $this->getEndDate();

        return $this;
    }
}

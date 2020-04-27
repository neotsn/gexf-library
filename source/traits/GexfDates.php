<?php

namespace tsn\traits;

use Exception;

/**
 * Trait GexfDates
 * Handles the common functionality for all elements in a Gexf XML object
 * @package tsn\traits
 */
trait GexfDates
{
    /** @var int|string Date in integer or string format YYYY-MM-DD */
    private $endDate;
    /** @var int|string Date in integer or string format YYYY-MM-DD */
    private $startDate;

    /**
     * @return int|string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return int|string
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param string|int $endDate
     *
     * @return $this
     * @throws \Exception
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $this->checkFormat($endDate);

        return $this;
    }

    /**
     * @param string|int $startDate
     *
     * @return $this
     * @throws \Exception
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $this->checkFormat($startDate);

        return $this;
    }

    /**
     * @param string|int $date
     *
     * @return mixed
     * @throws \Exception
     */
    private function checkFormat($date)
    {
        // Need a date value, and needs to be numeric or match the format
        if ($date && !is_numeric($date) && !preg_match("/\d{4}-\d{2}-\d{2}/", $date)) {
            // We have a date, but it's not numeric, and it doesn't match
            throw new Exception("Time not in right format");
        }

        return $date;
    }

    /**
     * @return string
     */
    private function renderStartEndDates()
    {
        return implode(' ', array_filter([
            ($this->getStartDate() ? ' start="' . $this->getStartDate() . '"' : ''),
            ($this->getEndDate() ? ' end="' . $this->getEndDate() . '"' : ''),
        ]));
    }
}

<?php

namespace tsn\traits;

/**
 * Trait GexfColor
 * Handles the color viz element for nodes and edges
 * @package tsn\traits
 */
trait GexfColor
{
    /** @var array Associative Array of RGBA values, with 'hex', 'a' as keys in the array */
    private $color = [];

    /**
     * @return array
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param int   $r 0-255
     * @param int   $g 0-255
     * @param int   $b 0-255
     * @param float $a Alpha 0 - 1 value
     *
     * @return $this
     */
    public function setColor($r = 0, $g = 0, $b = 0, $a = 1.0)
    {
        $this->color = [
            'r' => $r,
            'g' => $g,
            'b' => $b,
            'a' => (float)$a,
        ];

        return $this;
    }

    /**
     * @return string
     */
    private function renderColor()
    {
        return (!empty($this->getColor())) ? '<viz:color r="' . $this->getColor()['r'] . '" g="' . $this->getColor()['g'] . '" b="' . $this->getColor()['b'] . '" a="' . $this->getColor()['a'] . '"/>' : '';
    }
}

<?php

namespace tsn\Traits;

/**
 * Trait GexfColor
 * Handles the color viz element for nodes and edges
 * @package tsn\Traits
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
     * @param string $hex
     * @param float  $a Alpha 0 - 1 value
     *
     * @return $this
     */
    public function setColor($hex = 'FFFFFF', $a = 1.0)
    {
        $this->color = [
            'hex' => ltrim($hex, '#'),
            'a'   => (float)$a,
        ];

        return $this;
    }

    /**
     * @return string
     */
    private function renderColor()
    {
        return (!empty($this->getColor())) ? '<viz:color hex="' . $this->getColor()['hex'] . '" a="' . $this->getColor()['a'] . '"/>' : '';
    }
}

<?php

namespace tsn\traits;

use tsn\Gexf;
use tsn\GexfAttribute;
use tsn\GexfEdge;
use tsn\GexfNode;
use tsn\GexfSpell;

/**
 * Trait GexfCommonInnerElements
 * Handles the Attribute and Spell interior elements for Node and Edge objects
 * @package tsn\traits
 */
trait GexfCommonInnerElements
{
    /** @var \tsn\GexfAttribute[] Keyed by Attribute ID */
    private $attributes = [];
    /** @var \tsn\GexfSpell[] Keyed by Spell ID */
    private $spells = [];

    /**
     * @param \tsn\GexfAttribute $GexfAttribute
     *
     * @return $this
     */
    public function addAttribute(GexfAttribute $GexfAttribute)
    {
        $this->attributes[$GexfAttribute->getId()] = $GexfAttribute;

        return $this;
    }

    /**
     * @param \tsn\GexfSpell $GexfSpell
     *
     * @return $this
     */
    public function addSpell(GexfSpell $GexfSpell)
    {
        $this->spells[$GexfSpell->getId()] = $GexfSpell;

        return $this;
    }

    /**
     * @param $attributeName
     *
     * @return bool|string
     * @throws \Exception
     */
    public function getAttributeValue($attributeName)
    {
        $fakeAttribute = new GexfAttribute($attributeName, '');

        return (isset($this->attributes[$fakeAttribute->getId()]))
            ? $this->attributes[$fakeAttribute->getId()]->getValue()
            : false;
    }

    /**
     * @return \tsn\GexfAttribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return \tsn\GexfSpell[]
     */
    public function getSpells()
    {
        return $this->spells;
    }

    /**
     * Generate the <attvalues> XML Tag for use in the <node> & <edge> tag
     *
     * @param \tsn\Gexf $Gexf Pass in the outer object to spool up the GexfAttributes
     *                        for use in building the <attribute> elements later
     *
     * @return array|string|string[]
     */
    public function renderAttValues(Gexf &$Gexf)
    {
        return implode(array_filter([
            '<attvalues>',
            array_map(function ($GexfAttribute) use (&$Gexf) {

                if (is_a($this, GexfNode::class)) {
                    // Add it to the list for later
                    $Gexf->addNodeAttribute($GexfAttribute);
                } else if (is_a($this, GexfEdge::class)) {
                    $Gexf->addEdgeAttribute($GexfAttribute);
                }

                // Generate the XML it into the Edge
                return $GexfAttribute->renderAttValue();
            }, $this->getAttributes()),
            '</attvalues>',
        ]));
    }

    /**
     * Generate the <spells> XML Tag for use in the <node> & <edge> tags
     * Defines the times during which this Node lives.
     * @return string
     */
    public function renderSpells()
    {
        return (count($this->getSpells()))
            ? implode([
                '<spells>',
                implode(array_map(function (GexfSpell $GexfSpell) {
                    return $GexfSpell->render();
                }, $this->getSpells())),
                '</spells>',
            ])
            : '';
    }
}

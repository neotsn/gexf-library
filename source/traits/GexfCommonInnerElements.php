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
    /** @var \tsn\GexfAttribute[] These are keyed with ID+start+end date combined into a hash */
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
        $this->attributes[$GexfAttribute->getKey()] = clone $GexfAttribute;

        return $this;
    }

    /**
     * @param \tsn\GexfSpell $GexfSpell
     *
     * @return $this
     */
    public function addSpell(GexfSpell $GexfSpell)
    {
        $this->spells[$GexfSpell->getId()] = clone $GexfSpell;

        return $this;
    }

    /**
     * @param string      $attributeName Used to calculate the Key for retrieval
     * @param string|null $startDate     Used to calculate the Key for retrieval
     * @param string|null $endDate       Used to calculate the Key for retrieval
     *
     * @return mixed|null Mixed value if found, null if not
     * @throws \Exception
     */
    public function getAttributeValue($attributeName, $startDate = null, $endDate = null)
    {
        $fakeAttribute = (new GexfAttribute($attributeName, null))
            ->setStartEndDate($startDate, $endDate);

        return (isset($this->attributes[$fakeAttribute->getKey()]))
            ? $this->attributes[$fakeAttribute->getKey()]->getValue()
            : null;
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
    private function renderAttValues(Gexf &$Gexf)
    {
        return (count($this->getAttributes()))
            ? implode(array_filter([
                '<attvalues>',
                implode(array_map(function ($GexfAttribute) use (&$Gexf) {

                    if (is_a($this, GexfNode::class)) {
                        // Add it to the list for later
                        $Gexf->addNodeAttribute($GexfAttribute);
                    } else if (is_a($this, GexfEdge::class)) {
                        $Gexf->addEdgeAttribute($GexfAttribute);
                    }

                    // Generate the XML it into the Edge
                    return $GexfAttribute->renderAttValue();
                }, $this->getAttributes())),
                '</attvalues>',
            ]))
            : '';
    }

    /**
     * Generate the <spells> XML Tag for use in the <node> & <edge> tags
     * Defines the times during which this Node lives.
     * @return string
     */
    private function renderSpells()
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

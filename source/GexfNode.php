<?php

namespace tsn;

use Exception;
use tsn\Traits\GexfColor;
use tsn\Traits\GexfDates;

/**
 * Class GexfNode
 * @package tsn
 */
class GexfNode
{
    const SHAPE_DISC = 'disc';
    const SHAPE_SQUARE = 'square';
    const SHAPE_TRIANGLE = 'triangle';
    const SHAPE_DIAMOND = 'diamond';
    const SHAPE_IMAGE = 'image';

    use GexfDates;
    use GexfColor;

    /** @var string */
    private $id = "";
    /** @var string Only if shape='image */
    private $imageUrl = null;
    /** @var string */
    private $name = "";
    /** @var string */
    private $shape = 'disc';
    /** @var float A scale, non-negative float. 1.0 Default; 2.0 is 2x of 1.0 size. */
    private $size = 1.0;
    /** @var float[] Coordinates: x, y, z (height, distance from 0-plane) */
    private $coords = [];

    /** @var \tsn\GexfAttribute[] Keyed by Attribute ID */
    private $attributes = [];
    /** @var \tsn\GexfSpell[] Keyed by Spell ID */
    private $spells = [];
    /** @var \tsn\GexfNode[] */
    private $children = [];

    /**
     * GexfNode constructor.
     *
     * @param string          $name
     * @param string|null     $idprefix
     * @param string|int|null $startDate
     * @param string|int|null $endDate
     *
     * @throws \Exception
     */
    public function __construct($name, $idprefix = null, $startDate = null, $endDate = null)
    {
        $this->setNodeName($name);
        $this->setNodeId($idprefix);
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    /**
     * @param        $name
     * @param        $value
     * @param string $type
     * @param null   $startDate
     * @param null   $endDate
     *
     * @throws \Exception
     */
    public function addNodeAttribute($name, $value, $type = GexfAttribute::TYPE_STRING, $startDate = null, $endDate = null)
    {
        $attribute = new GexfAttribute($name, $value, $type, $startDate, $endDate);
        $this->attributes[$attribute->getAttributeId()] = $attribute;
    }

    /**
     * @param \tsn\GexfNode $node
     */
    public function addNodeChild(GexfNode $node)
    {
        $this->children[$node->getNodeId()] = $node;
    }

    /**
     * @param $start
     * @param $end
     *
     * @throws \Exception
     */
    public function addNodeSpell($start, $end)
    {
        $spell = new GexfSpell($start, $end);
        $this->spells[$spell->getSpellId()] = $spell;
    }

    /**
     * @param $child
     *
     * @return bool
     */
    public function doesChildExists(GexfNode $child)
    {
        return array_key_exists($child->getNodeId(), $this->getNodeChildren());
    }

    /**
     * @return float[]
     */
    public function getCoordinates()
    {
        return $this->coords;
    }

    /**
     * @param $attributeName
     *
     * @return bool|string
     * @throws \Exception
     */
    public function getNodeAttributeValue($attributeName)
    {
        $fakeAttribute = new GexfAttribute($attributeName, '');

        return (isset($this->attributes[$fakeAttribute->getAttributeId()]))
            ? $this->attributes[$fakeAttribute->getAttributeId()]->getAttributeValue()
            : false;
    }

    /**
     * @return \tsn\GexfAttribute[]
     */
    public function getNodeAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getNodeChildren()
    {
        return $this->children;
    }

    /**
     * @return string
     */
    public function getNodeId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->name;
    }

    /**
     * @return \tsn\GexfSpell[]
     */
    public function getNodeSpells()
    {
        return $this->spells;
    }

    /**
     * @return string
     */
    public function getShape()
    {
        return $this->shape;
    }

    /**
     * @return float
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Generate the <attvalues> XML Tag for use in the <node> tag
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
                // Add it to the list for later
                $Gexf->addNodeAttribute($GexfAttribute);

                // Generate the XML it into the Edge
                return $GexfAttribute->renderAttValue();
            }, $this->getNodeAttributes()),
            '</attvalues>',
        ]));
    }

    /**
     * @param \tsn\Gexf $Gexf
     *
     * @return string
     */
    public function renderNode(Gexf $Gexf)
    {
        return implode(array_filter([
            '<node id="' . $this->getNodeId() . '" label="' . $this->getNodeName() . '" ' . $this->renderStartEndDates() . '>',
            $this->renderColor(),
            ($this->getCoordinates()) ? '<viz:position x="' . $this->getCoordinates()['x'] . '" y="' . $this->getCoordinates()['y'] . '" z="' . $this->getCoordinates()['z'] . '"/>' : '',
            '<viz:size value="' . $this->getSize() . '"/>',
            '<viz:shape value=' . $this->getShape() . '"/>',
            $this->renderAttValues($Gexf),
            $this->renderSpells(),
            // Add this Node's Children: Recursive call to outer object inside of this one
            $Gexf->renderNodes($this->getNodeChildren()),
            '</node>',
        ]));
    }

    /**
     * Generate the <spells> XML Tag for use in the <node> tag
     * Defines the times during which this Node lives.
     * @return string
     */
    public function renderSpells()
    {
        return (count($this->getNodeSpells()))
            ? implode([
                '<spells>',
                implode(array_map(function (GexfSpell $GexfSpell) {
                    return $GexfSpell->render();
                }, $this->getNodeSpells())),
                '</spells>',
            ])
            : '';
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $z
     *
     * @return \tsn\GexfNode
     */
    public function setCoordinates($x = 0.0, $y = 0.0, $z = 0.0)
    {
        $this->coords = [
            'x' => (float)$x,
            'y' => (float)$y,
            'z' => (float)$z,
        ];

        return $this;
    }

    /**
     * @param string|null $idPrefix
     *
     * @return \tsn\GexfNode
     */
    public function setNodeId($idPrefix = null)
    {
        $this->id = ((isset($idPrefix)) ? $idPrefix : 'n-') . md5($this->name);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return \tsn\GexfNode
     */
    public function setNodeName($name)
    {
        $this->name = Gexf::cleanseString($name);

        return $this;
    }

    /**
     * @param $shapeEnum
     *
     * @return \tsn\GexfNode
     * @throws \Exception
     */
    public function setShape($shapeEnum)
    {
        if (in_array($shapeEnum, [self::SHAPE_DIAMOND, self::SHAPE_DISC, self::SHAPE_IMAGE, self::SHAPE_SQUARE, self::SHAPE_TRIANGLE])) {
            $this->shape = $shapeEnum;
        } else {
            throw new Exception('Invalid Node Shape provided: ' . $shapeEnum);
        }

        return $this;
    }

    /**
     * @param (float) $size
     *
     * @return \tsn\GexfNode
     */
    public function setSize($size)
    {
        $this->size = (float)$size;

        return $this;
    }
}

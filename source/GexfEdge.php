<?php

namespace tsn;

use Exception;
use tsn\Traits\GexfColor;
use tsn\Traits\GexfDates;

/**
 * Class GexfEdge
 * @package tsn
 */
class GexfEdge
{
    /** @var string From Source to Target */
    const GEXF_EDGE_DIRECTED = 'directed';
    /** @var string Just a line between the endpoints */
    const GEXF_EDGE_UNDIRECTED = 'undirected';
    /** @var string Bidirectional relationship */
    const GEXF_EDGE_MUTUAL = 'mutual';

    const SHAPE_DASHED = 'dashed';
    const SHAPE_DOTTED = 'dotted';
    const SHAPE_DOUBLE = 'double';
    const SHAPE_SOLID = 'solid';

    use GexfDates;
    use GexfColor;

    /** @var string */
    private $edgeType = 'undirected';
    /** @var string */
    private $id = '';
    /** @var string Another level of differentiation for Edges */
    private $kind = '';
    /** @var string A label for the edge */
    private $label = '';
    /** @var string */
    private $shape = self::SHAPE_SOLID;
    /** @var string The Source Node ID */
    private $sourceId = '';
    /** @var string The Target Node ID */
    private $targetId = '';
    /** @var float A scale, non-negative float. 1.0 Default; 2.0 is 2x of 1.0 thickness. */
    private $thickness = 1.0;
    /** @var int */
    private $weight = 1;

    /** @var \tsn\GexfAttribute[] */
    private $attributes = [];
    /** @var \tsn\GexfSpell[] */
    private $spells = [];

    /**
     * GexfEdge constructor.
     *
     * @param \tsn\GexfNode $sourceNode
     * @param \tsn\GexfNode $targetNode
     * @param int                                   $weight
     * @param string                                $edgeType
     * @param null                                  $startDate
     * @param null                                  $endDate
     *
     * @throws \Exception
     */
    public function __construct(GexfNode $sourceNode, GexfNode $targetNode, $weight, $edgeType, $startDate = null, $endDate = null)
    {
        $this->setEdgeSourceId($sourceNode);
        $this->setEdgeTargetId($targetNode);
        $this->setEdgeWeight($weight);
        $this->setEdgeType($edgeType);
        $this->setEdgeId();

        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $type
     * @param null   $startDate
     * @param null   $endDate
     *
     * @throws \Exception
     */
    public function addEdgeAttribute($name, $value, $type = GexfAttribute::TYPE_STRING, $startDate = null, $endDate = null)
    {
        $attribute = new GexfAttribute($name, $value, $type, $startDate, $endDate);
        $this->attributes[$attribute->getAttributeId()] = $attribute;
    }

    /**
     * @param string $startDate Date formatted as YYYY-MM-DD
     * @param string $endDate   Date formatted as YYYY-MM-DD
     *
     * @throws \Exception
     */
    public function addEdgeSpell($startDate, $endDate)
    {
        $spell = new GexfSpell($startDate, $endDate);
        $this->spells[$spell->getSpellId()] = $spell;
    }

    /**
     * @param int $weight
     */
    public function addToEdgeWeight($weight)
    {
        $this->weight += $weight;
    }

    /**
     * @return \tsn\GexfAttribute[]
     */
    public function getEdgeAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return string
     */
    public function getEdgeId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEdgeSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @return \tsn\GexfSpell[]
     */
    public function getEdgeSpells()
    {
        return $this->spells;
    }

    /**
     * @return string
     */
    public function getEdgeTargetId()
    {
        return $this->targetId;
    }

    /**
     * @return string
     */
    public function getEdgeType()
    {
        return $this->edgeType;
    }

    /**
     * @return int
     */
    public function getEdgeWeight()
    {
        return $this->weight;
    }

    /**
     * @return mixed
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    public function getShape()
    {
        return $this->shape;
    }

    public function getThickness()
    {
        return $this->thickness;
    }

    /**
     * Generate the set of <attvalue> XML Tags for use in the <edge> tag
     *
     * @param \tsn\Gexf $Gexf Pass in the outer object to spool up the GexfAttributes
     *                                                for use in building the <attribute> elements
     *
     * @return array|string|string[]
     */
    public function renderAttValues(Gexf &$Gexf)
    {
        return implode(array_filter([
            '<attvalues>',
            array_map(function ($GexfAttribute) use (&$Gexf) {
                // Add it to the list for later
                $Gexf->addEdgeAttribute($GexfAttribute);

                // Generate the XML it into the Edge
                return $GexfAttribute->renderAttValue();
            }, $this->getEdgeAttributes()),
            '</attvalues>',
        ]));
    }

    /**
     * Generate the <edge> XML Tag string for use in the <edges> tag.
     *
     * @param \tsn\Gexf $Gexf Pass in the outer object to spool up the GexfAttributes
     *                                                for use in building the <attribute> elements
     *
     * @return string
     */
    public function renderEdge(Gexf &$Gexf)
    {
        return implode([
            '<edge ' . implode(' ', array_filter([
                'id="' . $this->getEdgeId() . '"',
                'source="' . $this->getEdgeSourceId() . '"',
                'target="' . $this->getEdgeTargetId() . '"',
                'weight="' . $this->getEdgeWeight() . '"',
                $this->renderStartEndDates(),
                // Optional properties
                ($this->getLabel()) ? 'label="' . $this->getLabel() . '"' : null,
                ($this->getKind()) ? 'kind="' . $this->getKind() . '"' : null,
            ])) . '>',
            $this->renderColor(),
            '<viz:thickness value="' . $this->getThickness() . '"/>',
            '<viz:shape value="' . $this->getShape() . '"',
            $this->renderAttValues($Gexf),
            $this->renderSpells(),
            '</edge>',
        ]);
    }

    /**
     * Generate the <spells> XML Tag for use in the <edge> tag
     * Defines the times during which this edge lives.
     * @return string
     */
    public function renderSpells()
    {
        return (count($this->getEdgeSpells()))
            ? implode([
                '<spells>',
                implode(array_map(function (GexfSpell $GexfSpell) {
                    return $GexfSpell->render();
                }, $this->getEdgeSpells())),
                '</spells>',
            ])
            : '';
    }

    /**
     * Set the edge ID based on the node ids and the type
     * @return \tsn\GexfEdge
     */
    public function setEdgeId()
    {
        $sort = [$this->getEdgeSourceId(), $this->getEdgeTargetId()];
        if ($this->getEdgeType() == 'undirected')   // if undirected all concatenations need to be result in same id
        {
            sort($sort);
        }
        $this->id = 'e-' . implode('', $sort);

        return $this;
    }

    /**
     * @param \tsn\GexfNode $sourceNode
     *
     * @return \tsn\GexfEdge
     */
    public function setEdgeSourceId(GexfNode $sourceNode)
    {
        $this->sourceId = $sourceNode->getNodeId();

        return $this;
    }

    /**
     * @param \tsn\GexfNode $targetNode
     *
     * @return \tsn\GexfEdge
     */
    public function setEdgeTargetId(GexfNode $targetNode)
    {
        $this->targetId = $targetNode->getNodeId();

        return $this;
    }

    /**
     * @param $edgeType
     *
     * @return \tsn\GexfEdge
     */
    public function setEdgeType($edgeType)
    {
        $this->edgeType = $edgeType;

        return $this;
    }

    /**
     * @param $weight
     *
     * @return \tsn\GexfEdge
     */
    public function setEdgeWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @param $kind
     */
    public function setKind($kind)
    {
        $this->kind = Gexf::cleanseString($kind);
    }

    /**
     * @param $label
     */
    public function setLabel($label)
    {
        $this->label = Gexf::cleanseString($label);
    }

    /**
     * @param $shapeEnum
     *
     * @return \tsn\GexfEdge
     * @throws \Exception
     */
    public function setShape($shapeEnum)
    {
        if (in_array($shapeEnum, [self::SHAPE_SOLID, self::SHAPE_DASHED, self::SHAPE_DOTTED, self::SHAPE_DOUBLE])) {
            $this->shape = $shapeEnum;
        } else {
            throw new Exception('Invalid Edge Shape provided: ' . $shapeEnum);
        }

        return $this;
    }

    /**
     * @param float $thickness
     *
     * @return \tsn\GexfEdge
     */
    public function setThickness($thickness = 1.0)
    {
        $this->thickness = (float)$thickness;

        return $this;
    }
}

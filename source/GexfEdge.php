<?php

namespace tsn;

use Exception;
use tsn\traits\GexfColor;
use tsn\traits\GexfCommonInnerElements;
use tsn\traits\GexfDates;

/**
 * Class GexfEdge
 * @package tsn
 */
class GexfEdge
{
    /** @var string From Source to Target */
    const TYPE_DIRECTED = 'directed';
    /** @var string Just a line between the nodes */
    const TYPE_UNDIRECTED = 'undirected';
    /** @var string Bidirectional relationship */
    const TYPE_MUTUAL = 'mutual';

    const SHAPE_DASHED = 'dashed';
    const SHAPE_DOTTED = 'dotted';
    const SHAPE_DOUBLE = 'double';
    const SHAPE_SOLID = 'solid';

    use GexfDates;
    use GexfColor;
    use GexfCommonInnerElements;

    /** @var string */
    private $edgeType = self::TYPE_UNDIRECTED;
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

    /**
     * GexfEdge constructor.
     *
     * @param \tsn\GexfNode $sourceNode
     * @param \tsn\GexfNode $targetNode
     * @param int           $weight
     * @param string        $edgeType
     * @param null          $startDate
     * @param null          $endDate
     *
     * @throws \Exception
     */
    public function __construct(GexfNode $sourceNode, GexfNode $targetNode, $weight, $edgeType, $startDate = null, $endDate = null)
    {
        $this
            ->setSourceId($sourceNode)
            ->setTargetId($targetNode)
            ->setWeight($weight)
            ->setType($edgeType)
            ->setId()
            ->setStartEndDate($startDate, $endDate);
    }

    /**
     * @param int $weight
     */
    public function addToWeight($weight)
    {
        $this->weight += $weight;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return string
     */
    public function getShape()
    {
        return $this->shape;
    }

    /**
     * @return string
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @return string
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @return float
     */
    public function getThickness()
    {
        return $this->thickness;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->edgeType;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Generate the <edge> XML Tag string for use in the <edges> tag.
     *
     * @param \tsn\Gexf $Gexf Pass in the outer object to spool up the GexfAttributes
     *                        for use in building the <attribute> elements
     *
     * @return string
     */
    public function renderEdge(Gexf &$Gexf)
    {
        return implode([
            '<edge ' . implode(' ', array_filter([
                'id="' . $this->getId() . '"',
                'source="' . $this->getSourceId() . '"',
                'target="' . $this->getTargetId() . '"',
                'weight="' . $this->getWeight() . '"',
                'type="' . $this->getType() . '"',
                $this->renderStartEndDates(),
                // Optional properties
                ($this->getLabel()) ? 'label="' . $this->getLabel() . '"' : null,
                ($this->getKind()) ? 'kind="' . $this->getKind() . '"' : null,
            ])) . '>',
            $this->renderColor(),
            '<viz:thickness value="' . $this->getThickness() . '"/>',
            '<viz:shape value="' . $this->getShape() . '"/>',
            $this->renderAttValues($Gexf),
            $this->renderSpells(),
            '</edge>',
        ]);
    }

    /**
     * Set the edge ID based on the node ids and the type
     * @return \tsn\GexfEdge
     */
    public function setId()
    {
        $sort = [$this->getSourceId(), $this->getTargetId()];
        if ($this->getType() == GexfEdge::TYPE_UNDIRECTED) {
            // if undirected all concatenations need to be result in same id
            sort($sort);
        }
        $this->id = 'e-' . implode('', $sort);

        return $this;
    }

    /**
     * @param $kind
     *
     * @return \tsn\GexfEdge
     */
    public function setKind($kind)
    {
        $this->kind = Gexf::cleanseString($kind);

        return $this;
    }

    /**
     * @param $label
     *
     * @return \tsn\GexfEdge
     */
    public function setLabel($label)
    {
        $this->label = Gexf::cleanseString($label);

        return $this;
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
     * @param \tsn\GexfNode $sourceNode
     *
     * @return \tsn\GexfEdge
     */
    public function setSourceId(GexfNode $sourceNode)
    {
        $this->sourceId = $sourceNode->getId();

        return $this;
    }

    /**
     * @param \tsn\GexfNode $targetNode
     *
     * @return \tsn\GexfEdge
     */
    public function setTargetId(GexfNode $targetNode)
    {
        $this->targetId = $targetNode->getId();

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

    /**
     * @param $edgeType
     *
     * @return \tsn\GexfEdge
     */
    public function setType($edgeType)
    {
        $this->edgeType = $edgeType;

        // Directionality is taken into account with Type
        $this->setId();

        return $this;
    }

    /**
     * @param $weight
     *
     * @return \tsn\GexfEdge
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }
}

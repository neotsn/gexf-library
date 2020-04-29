<?php

namespace tsn;

use Exception;
use tsn\traits\GexfColor;
use tsn\traits\GexfCommonInnerElements;
use tsn\traits\GexfDates;

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
    use GexfCommonInnerElements;

    /** @var float[] Coordinates: x, y, z (height, distance from 0-plane) */
    private $coords = [];
    /** @var string */
    private $id = '';
    /** @var string Only if shape='image */
    private $imageUrl = null;
    /** @var string */
    private $name = '';
    /** @var string[] Array of parent GexfNode IDs */
    private $parents = [];
    /** @var string */
    private $shape = 'disc';
    /** @var float A scale, non-negative float. 1.0 Default; 2.0 is 2x of 1.0 size. */
    private $size = 1.0;

    /** @var \tsn\GexfNode[] */
    private $children = [];

    /**
     * GexfNode constructor.
     *
     * @param string          $name
     * @param string|null     $forcedId Explicitly define this object's ID; use `null` to auto-generate
     * @param string|int|null $startDate
     * @param string|int|null $endDate
     *
     * @throws \Exception
     */
    public function __construct($name, $forcedId = null, $startDate = null, $endDate = null)
    {
        $this
            ->setName($name)
            ->setId($forcedId)
            ->setStartEndDate($startDate, $endDate);
    }

    /**
     * Use this to indicate this node has children
     *
     * @param \tsn\GexfNode $GexfNode
     *
     * @return \tsn\GexfNode
     */
    public function addChildNode(GexfNode $GexfNode)
    {
        $this->children[$GexfNode->getId()] = clone $GexfNode;

        return $this;
    }

    /**
     * Use this to indicate this node has parents
     *
     * @param \tsn\GexfNode $GexfNode
     *
     * @return $this
     */
    public function addParentNode(GexfNode $GexfNode)
    {
        $this->parents[$GexfNode->getId()] = $GexfNode->getId();

        return $this;
    }

    /**
     * @param $ChildGexfNode
     *
     * @return bool
     */
    public function childExists(GexfNode $ChildGexfNode)
    {
        return array_key_exists($ChildGexfNode->getId(), $this->getChildNodes());
    }

    /**
     * @return array
     */
    public function getChildNodes()
    {
        return $this->children;
    }

    /**
     * @return float[]
     */
    public function getCoordinates()
    {
        return $this->coords;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getParentNodes()
    {
        return $this->parents;
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
     * @param \tsn\GexfNode $ParentGexfNode
     *
     * @return bool
     */
    public function parentExists(GexfNode $ParentGexfNode)
    {
        return array_key_exists($ParentGexfNode->getId(), $this->getParentNodes());
    }

    /**
     * @param \tsn\Gexf $Gexf
     *
     * @return string
     */
    public function renderNode(Gexf $Gexf)
    {
        return implode(array_filter([
            '<node ' . implode(' ', array_filter([
                'id="' . $this->getId() . '"',
                'label="' . $this->getName() . '"',
                $this->renderStartEndDates(),
                // If there is just 1 parent, toss it into a PID
                (count($this->getParentNodes()) == 1) ? 'pid="' . reset($this->parents) . '"' : null,
            ])) . '>',
            $this->renderColor(),
            ($this->getCoordinates()) ? '<viz:position x="' . $this->getCoordinates()['x'] . '" y="' . $this->getCoordinates()['y'] . '" z="' . $this->getCoordinates()['z'] . '"/>' : '',
            '<viz:size value="' . $this->getSize() . '"/>',
            '<viz:shape value="' . $this->getShape() . '"/>',
            $this->renderAttValues($Gexf),
            $this->renderSpells(),
            $this->renderParents(),
            // Add this Node's Children: Recursive call to outer object inside of this one
            $Gexf->renderNodes($this->getChildNodes()),
            '</node>',
        ]));
    }

    /**
     * @param float $x Left-right positioning
     * @param float $y Top-bottom positioning
     * @param float $z Up-Down positioning: A 'height' off the base XY-plane
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
     * @param string $name
     *
     * @return \tsn\GexfNode
     */
    public function setName($name)
    {
        $this->name = Gexf::cleanseString($name);

        return $this;
    }

    /**
     * @param string $shapeEnum
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
     * @param float $size
     *
     * @return \tsn\GexfNode
     */
    public function setSize($size)
    {
        $this->size = (float)$size;

        return $this;
    }

    /**
     * Generate the <parents> and <parent> XML elements for the <node> element
     * @return string
     */
    private function renderParents()
    {
        return (count($this->getParentNodes()) > 1)
            ? implode([
                '<parents>',
                implode(array_filter(array_map(function ($parentNodeId) {
                    return '<parent for="' . $parentNodeId . '"/>';
                }, $this->getParentNodes()))),
                '</parents>',
            ])
            : '';
    }

    /**
     * @param string|null $forcedId Explicitly define this object's ID; use `null` to auto-generate
     *
     * @return \tsn\GexfNode
     */
    private function setId($forcedId = null)
    {
        $this->id = (isset($forcedId)) ? Gexf::cleanseId($forcedId) : 'n-' . md5($this->name);

        return $this;
    }
}

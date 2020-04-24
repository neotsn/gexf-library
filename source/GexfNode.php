<?php

namespace tsn;

use Exception;
use tsn\Traits\GexfColor;
use tsn\traits\GexfCommonInnerElements;
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
    use GexfCommonInnerElements;

    /** @var float[] Coordinates: x, y, z (height, distance from 0-plane) */
    private $coords = [];
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

    /** @var \tsn\GexfNode[] */
    private $children = [];

    /**
     * GexfNode constructor.
     *
     * @param string          $name
     * @param string|null     $idPrefix
     * @param string|int|null $startDate
     * @param string|int|null $endDate
     *
     * @throws \Exception
     */
    public function __construct($name, $idPrefix = null, $startDate = null, $endDate = null)
    {
        $this->setName($name);
        $this->setId($idPrefix);
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
    }

    /**
     * @param \tsn\GexfNode $GexfNode
     *
     * @return \tsn\GexfNode
     */
    public function addChildNode(GexfNode $GexfNode)
    {
        $this->children[$GexfNode->getId()] = $GexfNode;

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
     * @param \tsn\Gexf $Gexf
     *
     * @return string
     */
    public function renderNode(Gexf $Gexf)
    {
        return implode(array_filter([
            '<node id="' . $this->getId() . '" label="' . $this->getName() . '" ' . $this->renderStartEndDates() . '>',
            $this->renderColor(),
            ($this->getCoordinates()) ? '<viz:position x="' . $this->getCoordinates()['x'] . '" y="' . $this->getCoordinates()['y'] . '" z="' . $this->getCoordinates()['z'] . '"/>' : '',
            '<viz:size value="' . $this->getSize() . '"/>',
            '<viz:shape value=' . $this->getShape() . '"/>',
            $this->renderAttValues($Gexf),
            $this->renderSpells(),
            // Add this Node's Children: Recursive call to outer object inside of this one
            $Gexf->renderNodes($this->getChildNodes()),
            '</node>',
        ]));
    }

    /**
     * @param float $x Left-right positioning
     * @param float $y Top-bottom positioning
     * @param float $z Up-Down positioning: A "height" off the base XY-plane
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
    public function setId($idPrefix = null)
    {
        $this->id = ((isset($idPrefix)) ? $idPrefix : 'n-') . md5($this->name);

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
}

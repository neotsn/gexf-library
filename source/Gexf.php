<?php

namespace tsn;

use Exception;
use tsn\traits\GexfDates;

/**
 * Class Gexf
 * Helper to build GEXF XML file for mapping & export
 * @package tsn
 */
class Gexf
{
    const MODE_STATIC = 'static';
    const MODE_DYNAMIC = 'dynamic';

    const TIMEFORMAT_DATE = 'date';
    /** @var string For consistent value and option XML generation */
    const DEFAULT_DELIMITER = ',';

    use GexfDates;

    /** @var \tsn\GexfNode[] */
    public $nodeObjects = [];
    /** @var \tsn\GexfEdge[] */
    public $edgeObjects = [];
    /** @var string */
    public $gexfFile = "";

    /** @var string */
    private $creator = "ThePizzy.net Labs";
    /** @var string */
    private $edgeType = GexfEdge::TYPE_UNDIRECTED;
    /** @var string[] */
    private $keywords = [];
    /** @var string */
    private $lastModifiedDate = '';
    /** @var string */
    private $mode = self::MODE_STATIC;
    /** @var bool */
    private $timeformat = self::TIMEFORMAT_DATE;
    /**  @var string */
    private $title = "";

    /** @var \tsn\GexfAttribute[] */
    private $nodeAttributeObjects = [
        self::MODE_STATIC  => [],
        self::MODE_DYNAMIC => [],
    ];
    /** @var \tsn\GexfAttribute[] */
    private $edgeAttributeObjects = [];

    /**
     * Gexf constructor.
     *
     * @param        $title
     * @param string $startDate
     * @param string $endDate
     *
     * @throws \Exception
     * @uses \tsn\Gexf::setStartEndDate()
     * @uses \tsn\Gexf::setTitle()
     */
    public function __construct($title, $startDate = null, $endDate = null)
    {
        $this
            ->setTitle($title)
            ->setStartEndDate($startDate, $endDate);
    }

    /**
     * Swap spaces for underscores
     * Swap everything that is non-alphnumeric/underscore out.
     *
     * @param $string
     *
     * @return string|string[]|null
     */
    public static function cleanseId($string)
    {
        return preg_replace('/\W/', '', preg_replace('/\s+/', '_', (string)$string));
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function cleanseString($string)
    {
        return str_replace("&", "&amp;", str_replace("'", "&quot;", str_replace('"', "'", strip_tags(trim($string)))));
    }

    /**
     * @param \tsn\GexfEdge $GexfEdge
     *
     * @return string
     */
    public function addEdge(GexfEdge $GexfEdge)
    {
        // if edge did not exist, add to list
        if (array_key_exists($GexfEdge->getId(), $this->edgeObjects) == false) {
            $this->edgeObjects[$GexfEdge->getId()] = clone $GexfEdge;
        } else {
            // else add weight to existing edge
            $this->edgeObjects[$GexfEdge->getId()]->addToWeight($GexfEdge->getWeight());
        }

        return $GexfEdge->getId();
    }

    /**
     * @note This is Public, but are intended to be private, as they are only used in a Trait during render compilation
     * Add an Edge Attribute record
     *
     * @param \tsn\GexfAttribute $GexfAttribute
     *
     * @uses \tsn\Gexf::getEdgeAttributeObjects()
     */
    public function addEdgeAttribute(GexfAttribute $GexfAttribute)
    {
        if (array_key_exists($GexfAttribute->getId(), $this->getEdgeAttributeObjects()) === false) {
            $this->edgeAttributeObjects[$GexfAttribute->getId()] = clone $GexfAttribute;
        }
    }

    /**
     * Add Keywords into the array
     *
     * @param string|string[] $keywords Array or CSV of keywords
     *
     * @return \tsn\Gexf
     */
    public function addKeywords($keywords)
    {
        $keywords = (is_array($keywords)) ? $keywords : explode(self::DEFAULT_DELIMITER, $keywords);

        $this->keywords = array_unique(array_merge($this->keywords, array_filter(array_map(function ($word) {
            return trim(strtolower($word));
        }, $keywords))));

        return $this;
    }

    /**
     * @param \tsn\GexfNode $GexfNode
     *
     * @return string
     */
    public function addNode(GexfNode $GexfNode)
    {
        if (!$this->nodeExists($GexfNode)) {
            $this->nodeObjects[$GexfNode->getId()] = clone $GexfNode;
        }

        return $GexfNode->getId();
    }

    /**
     * @note This is Public, but are intended to be private, as they are only used in a Trait during render compilation
     * Add a Node Attribute record
     *
     * @param \tsn\GexfAttribute $GexfAttribute
     *
     * @throws \Exception
     * @uses \tsn\Gexf::getNodeAttributeObjects()
     */
    public function addNodeAttribute(GexfAttribute $GexfAttribute)
    {
        if (array_key_exists($GexfAttribute->getId(), $this->getNodeAttributeObjects($GexfAttribute->getMode())) === false) {
            $this->nodeAttributeObjects[$GexfAttribute->getMode()][$GexfAttribute->getId()] = clone $GexfAttribute;
        }
    }

    /**
     * @return string
     */
    public function getDefaultEdgeType()
    {
        return $this->edgeType;
    }

    /**
     * If a node already exists for the ID, retrieve it
     *
     * @param \tsn\GexfNode $GexfNode
     *
     * @return \tsn\GexfNode
     */
    public function getNode(GexfNode $GexfNode)
    {

        if ($this->nodeExists($GexfNode)) {
            $GexfNode = $this->nodeObjects[$GexfNode->getId()];
        }

        return $GexfNode;
    }

    /**
     * @param \tsn\GexfNode $GexfNode
     *
     * @return bool
     */
    public function nodeExists(GexfNode $GexfNode)
    {
        return array_key_exists($GexfNode->getId(), $this->nodeObjects);
    }

    /**
     * Prepare and store the XML string
     */
    public function render()
    {
        /**
         * @note These are all done in a specific order for processing...
         *       and put into a different order for output
         */
        $nodes = $this->renderNodes($this->nodeObjects);
        $edges = $this->renderEdges($this->edgeObjects);
        $nodeAttributes = $this->renderNodeAttributes();
        $edgeAttributes = $this->renderEdgeAttributes();

        $this->gexfFile = '<?xml version="1.0" encoding="UTF-8"?>
<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:viz="http://www.gexf.net/1.2draft/viz" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.2draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <meta lastmodifieddate="' . $this->lastModifiedDate . '">' .
            implode(array_filter([
                ($this->creator) ? '<creator>' . $this->creator . '</creator>' : null,
                ($this->title) ? '<description>' . $this->title . '</description>' : null,
                ($this->keywords) ? '<keywords>' . implode(', ', $this->keywords) . '</keywords>' : null,
            ])) . '</meta>
    <graph ' . implode(' ', array_filter([
                'defaultedgetype="' . $this->edgeType . '"',
                'mode="' . $this->mode . '"',
                (!empty($this->timeformat)) ? 'timeformat="' . $this->timeformat . '"' : null,
                $this->renderStartEndDates(),
            ]))
            . '>' . implode(array_filter([$nodeAttributes, $edgeAttributes, $nodes, $edges])) . '</graph>
</gexf>';
    }

    /**
     * @param \tsn\GexfNode[] $nodeObjects
     *
     * @return string
     */
    public function renderNodes(array $nodeObjects)
    {
        return (count($nodeObjects))
            ? implode([
                '<nodes>',
                implode(array_map(function (GexfNode $GexfNode) {
                    return $GexfNode->renderNode($this);
                }, $nodeObjects)),
                '</nodes>',
            ])
            : '';

    }

    /**
     * @param string $creator
     *
     * @return \tsn\Gexf
     */
    public function setCreator($creator)
    {
        $this->creator = self::cleanseString($creator);

        return $this;
    }

    /**
     * @param string $edgeType Either GexfEdge::GEXF_EDGE_DIRECTED or GexfEdge::GEXF_EDGE_UNDIRECTED
     *
     * @return \tsn\Gexf
     * @throws \Exception
     */
    public function setEdgeType($edgeType)
    {
        if (in_array($edgeType, [GexfEdge::TYPE_DIRECTED, GexfEdge::TYPE_UNDIRECTED, GexfEdge::TYPE_MUTUAL])) {
            $this->edgeType = $edgeType;
        } else {
            throw new Exception("Unsupported edge type: $edgeType");
        }

        return $this;
    }

    /**
     * @param $modifiedDate
     *
     * @return \tsn\Gexf
     * @throws \Exception
     */
    public function setLastModifiedDate($modifiedDate)
    {
        $this->lastModifiedDate = $this->checkFormat($modifiedDate);

        return $this;
    }

    /**
     * @param string $modeEnum Either Gexf::GEXF_MODE_STATIC or Gexf::GEXF_MODE_DYNAMIC
     *
     * @return \tsn\Gexf
     * @throws \Exception
     */
    public function setMode($modeEnum)
    {
        if (in_array($modeEnum, [self::MODE_STATIC, self::MODE_DYNAMIC])) {
            $this->mode = $modeEnum;
        } else {
            throw new Exception("Unsupported mode: $modeEnum");
        }

        return $this;
    }

    /**
     * @param string $formatEnum Currently only Gexf::GEXF_TIMEFORMAT_DATE
     *
     * @return \tsn\Gexf
     * @throws \Exception
     */
    public function setTimeFormat($formatEnum)
    {
        if ($formatEnum == self::TIMEFORMAT_DATE || empty($formatEnum)) {
            $this->timeformat = ($formatEnum) ?: false;
        } else {
            throw new Exception("Unsupported time format: $formatEnum");
        }

        return $this;
    }

    /**
     * @param string $title
     *
     * @return \tsn\Gexf
     */
    public function setTitle($title)
    {
        $this->title = self::cleanseString($title);

        return $this;
    }

    /**
     * @return \tsn\GexfAttribute[]
     */
    private function getEdgeAttributeObjects()
    {
        return $this->edgeAttributeObjects;
    }

    /**
     * @param string $modeEnum One of Gexf::MODE_* Constants
     *
     * @return \tsn\GexfAttribute[]
     * @throws \Exception
     */
    private function getNodeAttributeObjects($modeEnum)
    {
        if (in_array($modeEnum, [self::MODE_DYNAMIC, self::MODE_STATIC])) {
            return $this->nodeAttributeObjects[$modeEnum];
        } else {
            throw new Exception('Invalid Mode provided: ' . $modeEnum);
        }
    }

    /**
     * @return string
     */
    private function renderEdgeAttributes()
    {
        return (count($this->edgeAttributeObjects))
            ? implode([
                '<attributes class="edge">',
                implode(array_map(function ($GexfAttribute) {
                    return $GexfAttribute->renderAttribute();
                }, $this->edgeAttributeObjects)),
                '</attributes>',
            ])
            : '';
    }

    /**
     * @param \tsn\GexfEdge[] $edgeObjects
     *
     * @return string
     */
    private function renderEdges($edgeObjects)
    {
        return (count($edgeObjects))
            ? implode([
                '<edges>',
                implode(array_map(function (GexfEdge $GexfEdge) {
                    return $GexfEdge->renderEdge($this);
                }, $edgeObjects)),
                '</edges>',
            ])
            : '';
    }

    /**
     * @return string
     */
    private function renderNodeAttributes()
    {
        $output = [];

        if (count($this->nodeAttributeObjects[self::MODE_STATIC])) {
            $output[] = implode([
                '<attributes class="node" mode="static">',
                implode(array_map(function (GexfAttribute $GexfAttribute) {
                    return $GexfAttribute->renderAttribute();
                }, $this->nodeAttributeObjects[self::MODE_STATIC])),
                '</attributes>',
            ]);
        }

        if (count($this->nodeAttributeObjects[self::MODE_DYNAMIC])) {
            $output[] = implode([
                '<attributes class="node" mode="dynamic">',
                implode(array_map(function (GexfAttribute $GexfAttribute) {
                    return $GexfAttribute->renderAttribute();
                }, $this->nodeAttributeObjects[self::MODE_DYNAMIC])),
                '</attributes>',
            ]);
        }

        return implode($output);
    }
}

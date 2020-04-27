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
    private $nodeAttributeObjects = [];
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
     */
    public function __construct($title, $startDate = null, $endDate = null)
    {
        $this->setTitle($title);
        $this->setStartDate($startDate);
        $this->setEndDate($endDate);
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
     * @throws \Exception
     */
    public function addEdge(GexfEdge $GexfEdge)
    {
        // if edge did not exist, add to list
        if (array_key_exists($GexfEdge->getId(), $this->edgeObjects) == false) {
            $this->edgeObjects[$GexfEdge->getId()] = $GexfEdge;
        } else {
            // else add weight to existing edge
            $this->edgeObjects[$GexfEdge->getId()]->addToWeight($GexfEdge->getWeight());
        }

        return $GexfEdge->getId();
    }

    /**
     * Add an Edge Attribute record
     *
     * @param \tsn\GexfAttribute $GexfAttribute
     */
    public function addEdgeAttribute(GexfAttribute $GexfAttribute)
    {
        if (array_key_exists($GexfAttribute->getId(), $this->getEdgeAttributeObjects()) === false) {
            $this->edgeAttributeObjects[$GexfAttribute->getId()] = $GexfAttribute;
        }
    }

    /**
     * @param \tsn\GexfEdge  $GexfEdge
     * @param \tsn\GexfSpell $GexfSpell
     */
    public function addEdgeSpell(GexfEdge $GexfEdge, GexfSpell $GexfSpell)
    {
        if (array_key_exists($GexfEdge->getId(), $this->edgeObjects) == false) {
            die('make an edge before you add a spell');
        }
        $this->edgeObjects[$GexfEdge->getId()]->addSpell($GexfSpell);
    }

    /**
     * Add Keywords into the array
     *
     * @param $keywords
     */
    public function addKeywords($keywords)
    {
        $this->keywords = array_unique(array_merge($this->keywords, array_filter(array_map(function ($word) {
            return trim(strtolower($word));
        }, explode(',', $keywords)))));
    }

    /**
     * @param \tsn\GexfNode $GexfNode
     *
     * @return string
     */
    public function addNode(GexfNode $GexfNode)
    {
        if (!$this->nodeExists($GexfNode)) {
            $this->nodeObjects[$GexfNode->getId()] = $GexfNode;
        }

        return $GexfNode->getId();
    }

    /**
     * Add a Node Attribute record
     *
     * @param \tsn\GexfAttribute $GexfAttribute
     */
    public function addNodeAttribute(GexfAttribute $GexfAttribute)
    {
        if (array_key_exists($GexfAttribute->getId(), $this->getNodeAttributeObjects()) === false) {
            $this->nodeAttributeObjects[$GexfAttribute->getId()] = $GexfAttribute;
        }
    }

    /**
     * @return \tsn\GexfAttribute[]
     */
    public function getEdgeAttributeObjects()
    {
        return $this->edgeAttributeObjects;
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
     * @return \tsn\GexfAttribute[]
     */
    public function getNodeAttributeObjects()
    {
        return $this->nodeAttributeObjects;
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

        $this->gexfFile = chr(239) . chr(187) . chr(191) . '<?xml version="1.0" encoding="UTF-8"?>
		<gexf xmlns="http://www.gexf.net/1.3draft"
		    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		    xsi:schemaLocation="http://www.gexf.net/1.3draft http://www.gexf.net/1.3draft/gexf.xsd"
		    version="1.3">
			<meta lastmodifieddate="' . $this->lastModifiedDate . '">
				<creator>' . $this->creator . '</creator>
				<description>' . $this->title . '</description>
				<keywords>' . implode(', ', $this->keywords) . '</keywords>
			</meta>
			<graph defaultedgetype="' . $this->edgeType . '" mode="' . $this->mode . '" ' . (!empty($this->timeformat) ? 'timeformat="' . $this->timeformat . '"' : '') . ' ' . $this->renderStartEndDates() . '>
				' . $nodeAttributes . '
				' . $edgeAttributes . '
				' . $nodes . '
				' . $edges . '
			</graph>
		</gexf>';
    }

    /**
     * @param \tsn\GexfNode[] $nodeObjects
     *
     * @return string
     */
    public function renderNodes(array $nodeObjects)
    {
        return implode([
            '<nodes>',
            implode(array_map(function (GexfNode $GexfNode) {
                return $GexfNode->renderNode($this);
            }, $nodeObjects)),
            '</nodes>',
        ]);

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
        return implode([
            '<edges>',
            implode(array_map(function (GexfEdge $GexfEdge) {
                return $GexfEdge->renderEdge($this);
            }, $edgeObjects)),
            '</edges>',
        ]);
    }

    /**
     * @return string
     */
    private function renderNodeAttributes()
    {
        return (count($this->nodeAttributeObjects))
            ? implode([
                '<attributes class="node">',
                implode(array_map(function (GexfAttribute $GexfAttribute) {
                    return $GexfAttribute->renderAttribute();
                }, $this->nodeAttributeObjects)),
                '</attributes>',
            ])
            : '';
    }
}

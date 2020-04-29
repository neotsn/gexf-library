<?php

namespace tsn;

use Exception;
use tsn\traits\GexfDates;

/**
 * Class GexfAttribute
 * @package tsn
 */
class GexfAttribute
{

    const TYPE_INTEGER = 'integer';
    const TYPE_LONG = 'long';
    const TYPE_DOUBLE = 'double';
    const TYPE_FLOAT = 'float';
    /** @var string A 'true' or 'false' string for the boolean value */
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    /**
     * @var string A CSV of values; In place of 3 attributes `foo=true`, `bar=true`, `baz=false`,
     *             a single liststring for `foo,bar`. Takes comma, semicolon, or pipe delimiters
     * @note    this is an unsafe type as the value is parse, and must be cleansed before setting. No escaped characters respected.
     * @example <attributes>
     *              <attribute id="0" title="hobby" type="liststring">
     *                  <options>ski|dance|photo</options>
     *              </attribute>
     *          </attributes>
     *          <nodes>
     *              <node id="42" label="a node">
     *                  <attvalues>
     *                      <attvalue for="0" value="dance|ski">
     *                  </attvalues>
     *              </node>
     *          </nodes>
     */
    const TYPE_LISTSTRING = 'liststring';
    const TYPE_URI = 'anyURI';

    use GexfDates;

    /** @var string|int|null Default Value to use */
    private $defaultValue = null;
    /** @var string */
    private $id = '';
    /** @var array */
    private $listStringOptions = [];
    /** @var string Set whether this is a static or dynamic attribute */
    private $mode = Gexf::MODE_STATIC;
    /** @var string */
    private $name = '';
    /** @var string */
    private $type = self::TYPE_STRING;
    /** @var string */
    private $value = '';

    /**
     * GexfAttribute constructor.
     *
     * @param string      $name
     * @param string      $value
     * @param string      $typeEnum The Data Type
     * @param string|null $forcedId Explicitly define this object's ID; use `null` to auto-generate
     * @param string      $modeEnum Change to Dynamic to use start/end dates
     * @param string      $startDate
     * @param string      $endDate
     *
     * @throws \Exception
     */
    public function __construct($name, $value, $typeEnum = self::TYPE_STRING, $forcedId = null, $modeEnum = Gexf::MODE_STATIC, $startDate = null, $endDate = null)
    {
        $this
            ->setName($name)
            ->setId($forcedId)
            ->setType($typeEnum)
            ->setValue($value)
            ->setMode($modeEnum)
            ->setStartEndDate($startDate, $endDate);
    }

    /**
     * @param array|string $options An array or delimited [, ; |] string
     *
     * @return \tsn\GexfAttribute
     * @uses \tsn\GexfAttribute::processListStringOptions()
     */
    public function addListStringOptions($options)
    {
        $options = self::processListStringOptions($options);

        if (is_array($options)) {
            $this->listStringOptions = array_unique(array_filter(array_merge($this->listStringOptions, $options)));
        }

        return $this;
    }

    /**
     * @param array|string    $options An array or delimited [, ; |] string
     * @param int|string|null $default The Default value to use, if any
     *
     * @return $this
     * @throws \Exception
     */
    public function asListStringType($options, $default = null)
    {
        return $this
            ->setType(self::TYPE_LISTSTRING)
            ->addListStringOptions($options)
            ->setDefaultValue($default);
    }

    /**
     * @return int|string|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * For spooling into the <attvalues> element of <edge> and <node> elements
     * @note These are keyed with start/end date taken into consideration
     *       along with the ID, because the same attribute should overwrite,
     *       but the same attribute with different value on different dates
     *       should be appended
     * @return string
     */
    public function getKey()
    {
        return 'av-' . md5($this->getName() . '-s-' . $this->getStartDate() . '-e-' . $this->getEndDate());
    }

    /**
     * @return array
     */
    public function getListStringOptions()
    {
        return $this->listStringOptions;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Generate the <attvalue> XML string for this Attribute to be used after an <edge> & <node> tag.
     * @return string
     */
    public function renderAttValue()
    {
        return '<attvalue ' . implode(' ', array_filter([
                'for="' . $this->getId() . '"',
                'value="' . $this->getValue() . '"',
                $this->renderStartEndDates(),
            ])) . '/>';
    }

    /**
     * Generate the <attribute> XML String for this Attribute to be used in a <attributes> tag.
     * @return string
     */
    public function renderAttribute()
    {
        // Extract the Default tag, if one, to prevent self-closing element
        $defaultXml = (!is_null($this->getDefaultValue())) ? '<default>' . $this->getDefaultValue() . '</default>' : null;

        // Extract ListString Type, if set, to prevent self-closing element
        $listStringOptions = ($this->getType() == self::TYPE_LISTSTRING) ? '<options>' . implode(Gexf::DEFAULT_DELIMITER, $this->getListStringOptions()) . '</options>' : null;

        return ($defaultXml || $listStringOptions)
            ? implode(array_filter([
                '<attribute id="' . $this->getId() . '" title="' . $this->getName() . '" type="' . $this->getType() . '">',
                $defaultXml,
                $listStringOptions,
                '</attribute>',
            ]))
            : '<attribute id="' . $this->getId() . '" title="' . $this->getName() . '" type="' . $this->getType() . '"/>';
    }

    /**
     * @param string|int|null $default
     *
     * @return \tsn\GexfAttribute
     * @throws \Exception
     */
    public function setDefaultValue($default)
    {
        if (!is_null($default)) {
            if ($this->getType() == self::TYPE_LISTSTRING && !in_array($default, $this->getListStringOptions())) {
                throw new Exception('Default List String Value not available in Options: ' . $default);
            }

            $this->defaultValue = Gexf::cleanseString($default);
        }

        return $this;
    }

    /**
     * @param string $modeEnum Either Gexf::GEXF_MODE_STATIC or Gexf::GEXF_MODE_DYNAMIC
     *
     * @return \tsn\GexfAttribute
     * @throws \Exception
     */
    public function setMode($modeEnum)
    {
        if (in_array($modeEnum, [Gexf::MODE_STATIC, Gexf::MODE_DYNAMIC])) {
            $this->mode = $modeEnum;
        } else {
            throw new Exception('Unsupported mode: ' . $modeEnum);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return \tsn\GexfAttribute
     */
    public function setName($name)
    {
        $this->name = Gexf::cleanseString($name);

        return $this;
    }

    /**
     * @param $typeEnum
     *
     * @return \tsn\GexfAttribute
     * @throws \Exception
     */
    public function setType($typeEnum)
    {
        if (in_array($typeEnum, [self::TYPE_BOOLEAN, self::TYPE_DOUBLE, self::TYPE_FLOAT, self::TYPE_INTEGER, self::TYPE_LISTSTRING, self::TYPE_LONG, self::TYPE_STRING, self::TYPE_URI])) {
            $this->type = $typeEnum;
        } else {
            throw new Exception('Invalid Attribute Type provided: ' . $typeEnum);
        }

        return $this;
    }

    /**
     * @param mixed $value Array|String for ListString, Sting for everything else
     *
     * @return \tsn\GexfAttribute
     */
    public function setValue($value)
    {
        if ($this->getType() == self::TYPE_LISTSTRING) {
            $value = implode(Gexf::DEFAULT_DELIMITER, self::processListStringOptions($value));
        } else {
            $value = Gexf::cleanseString($value);
        }

        $this->value = $value;

        return $this;
    }

    /**
     * @param $options
     *
     * @return array|false|string[]
     */
    private static function processListStringOptions($options)
    {
        if (!is_array($options)) {
            $options = (string)$options;

            switch (true) {
                case strpos($options, ',') !== false:
                    $delimiter = ',';
                    break;
                case strpos($options, ';') !== false:
                    $delimiter = ';';
                    break;
                case strpos($options, '|') !== false:
                    $delimiter = '|';
                    break;
                default:
                    $delimiter = null;
                    break;
            }

            $options = ($delimiter)
                // Convert to array ond delimiter
                ? explode($delimiter, $options)
                // No delimiter, so treat as single value
                : [$options];

            $options = array_map(function ($option) {
                return trim($option);
            }, $options);
        }

        return $options;
    }

    /**
     * Sets the attribute ID to a hash of the name, start, and end date (as available)
     * Because the same attribute can be pumped into the node more than once per date
     *
     * @param string|null $forcedId Explicitly define this object's ID; use `null` to auto-generate
     *
     * @return \tsn\GexfAttribute
     */
    private function setId($forcedId = null)
    {
        $this->id = (isset($forcedId)) ? Gexf::cleanseId($forcedId) : 'a-' . md5($this->getName());

        return $this;
    }
}

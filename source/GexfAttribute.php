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
    /** @var string For consistent value and option XML generation */
    const DEFAULT_DELIMITER = ',';

    const TYPE_INTEGER = 'integer';
    const TYPE_LONG = 'long';
    const TYPE_DOUBLE = 'double';
    const TYPE_FLOAT = 'float';
    /** @var string A "true" or "false" string for the boolean value */
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

    /** @var string */
    private $id = "";
    /** @var array */
    private $listStringOptions = [];
    /** @var string|int|null Default ListString option to use */
    private $listStringDefault = null;
    /** @var string */
    private $name = "";
    /** @var string */
    private $type = self::TYPE_STRING;
    /** @var string */
    private $value = "";

    /**
     * GexfAttribute constructor.
     *
     * @param string $name
     * @param string $value
     * @param string $type
     * @param string $startDate
     * @param string $endDate
     *
     * @throws \Exception
     */
    public function __construct($name, $value, $type = self::TYPE_STRING, $startDate = null, $endDate = null)
    {
        $this
            ->setName($name)
            ->setId()
            ->setType($type)
            ->setValue($value)
            ->setStartDate($startDate)
            ->setEndDate($endDate);
    }

    /**
     * @param array|string $options An array or delimited [, ; |] string
     *
     * @return \tsn\GexfAttribute
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
            ->setListStringDefault($default);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|string|null
     */
    public function getListStringDefault()
    {
        return $this->listStringDefault;
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
        return '<attvalue for="' . $this->getId() . '" value="' . $this->getValue() . '" ' . $this->renderStartEndDates() . '/>';
    }

    /**
     * Generate the <attribute> XML String for this Attribute to be used in a <attributes> tag.
     * @return string
     */
    public function renderAttribute()
    {
        return ($this->getType() == self::TYPE_LISTSTRING)
            ? implode(array_filter([
                '<attribute id="' . $this->getId() . '" title="' . $this->getName() . '" type="' . $this->getType() . '">',
                ($this->getListStringDefault()) ? '<default>' . $this->getListStringDefault() . '</default>' : null,
                '<options>' . implode(self::DEFAULT_DELIMITER, $this->getListStringOptions()) . '</options>',
                '</attribute>',
            ]))
            : '<attribute id="' . $this->getId() . '" title="' . $this->getName() . '" type="' . $this->getType() . '"/>';
    }

    /**
     * Sets the attribute ID to a hash of the name
     * @return \tsn\GexfAttribute
     */
    public function setId()
    {
        $this->id = "a-" . md5($this->getName());

        return $this;
    }

    /**
     * @param $default
     *
     * @return \tsn\GexfAttribute
     * @throws \Exception
     */
    public function setListStringDefault($default)
    {
        if (in_array($default, $this->getListStringOptions())) {
            $this->listStringDefault = $default;

        } else {
            throw new Exception('Default List String Value not available in Options: ' . $default);
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
     * @param string $value
     *
     * @return \tsn\GexfAttribute
     */
    public function setValue($value)
    {
        if ($this->getType() == self::TYPE_LISTSTRING) {
            $value = implode(self::DEFAULT_DELIMITER, self::processListStringOptions($value));
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
        }

        return $options;
    }
}

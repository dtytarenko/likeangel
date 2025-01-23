<?php

/**
 * Class WC_Keycrm_Abstract_Builder.
 */
abstract class WC_Keycrm_Abstract_Builder implements WC_Keycrm_Builder_Interface
{
    /** @var array|mixed $data */
    protected $data;

    /**
     * @param array|mixed $data
     *
     * @return \WC_Keycrm_Abstract_Builder
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return $this|\WC_Keycrm_Builder_Interface
     */
    public function reset()
    {
        $this->data = array();
        return $this;
    }

    /**
     * Returns key if it's present in data array (or object which implements ArrayAccess).
     * Returns default value if key is not present in data, or data is not accessible as array.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function dataValue($key, $default = '')
    {
        return self::arrayValue($this->data, $key, $default);
    }

    /**
     * Returns key from array if it's present in array
     *
     * @param array|\ArrayObject $data
     * @param mixed              $key
     * @param string             $default
     *
     * @return mixed|string
     */
    protected static function arrayValue($data, $key, $default = '')
    {
        if (!is_array($data) && !($data instanceof ArrayAccess)) {
            return $default;
        }

        if (array_key_exists($key, $data) && !empty($data[$key])) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @return \WC_Keycrm_Builder_Interface
     */
    abstract public function build();

    /**
     * Returns builder result. Should return null if WC_Keycrm_Abstract_Builder::isBuilt() == false.
     *
     * @return mixed|null
     */
    abstract public function getResult();
}

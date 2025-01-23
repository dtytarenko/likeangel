<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   KeyCRM <integration@keycrm.app>
 * @license  http://keycrm.app Proprietary
 * @link     http://keycrm.app
 * @see      http://help.keycrm.app
 */

/**
 * Class WC_Keycrm_Abstracts_Data
 */
abstract class WC_Keycrm_Abstracts_Data
{
    /** @var string */
    protected $filter_name;

    /** @var array */
    protected $data = array();

    /**
     * @return void
     */
    abstract public function reset_data();

    /**
     * @param $data
     *
     * @return self
     */
    abstract public function build($data);

    protected function set_data_field($field, $value)
    {
        if (isset($this->data[$field]) && \gettype($value) !== \gettype($this->data[$field])) {
            return false;
        }

        $this->data[$field] = $value;

        return true;
    }

    /**
     * @param $fields
     */
    protected function set_data_fields($fields)
    {
        foreach ($fields as $field => $value) {
            $this->set_data_field($field, $value);
        }
    }

    /**
     * @return array
     */
    public function get_data()
    {
        return apply_filters('keycrm_before_send_' . $this->filter_name, WC_Keycrm_Plugin::clearArray($this->data));
    }

    /**
     * @return array
     */
    protected function get_data_without_filters()
    {
        return $this->data;
    }
}

<?php

class Zefram_Db_Select extends Zend_Db_Select
{
    /**
     * Adds a WHERE condition to the query by AND.
     *
     * @param string|array $cond
     *     The WHERE condition.
     * @param mixed $value OPTIONAL
     *     The value to quote into the condition, used when condition
     *     is not an array
     * @param int $type OPTIONAL
     *     The type of the given value, used when condition is not an array
     * @return Zefram_Db_Select
     *     This select object
     */
    public function where($cond, $value = null, $type = null)
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    parent::where($value);
                } else {
                    parent::where($key, $value);
                }
            }
        } else {
            parent::where($cond, $value, $type); 
        }
        return $this;
    }

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * @param string|array $cond
     *     The WHERE condition.
     * @param mixed $value OPTIONAL
     *     The value to quote into the condition, used when condition
     *     is not an array
     * @param int $type OPTIONAL
     *     The type of the given value, used when condition is not an array
     * @return Zefram_Db_Select
     *     This select object
     */
    public function orWhere($cond, $value = null, $type = null)
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    parent::orWhere($value);
                } else {
                    parent::orWhere($key, $value);
                }
            }
        } else {
            parent::orWhere($cond, $value, $type); 
        }
        return $this;
    }
}

<?php
namespace kak\models\behaviors;

use yii\base\Behavior;
use yii\base\ErrorException;
use yii\base\ExitException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class CallbackBehavior extends Behavior
{

    const METHOD_JSON = 'json';
    const METHOD_STRING = 'string';

    public $params;
    private $_values = [];


    /**
     * Events list
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'onPrepareCallBack',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'onPrepareCallBack',

        ];
    }

    public function onPrepareCallBack()
    {
        $owner = $this->owner;
        // Save data
        foreach ($this->params as $attributeName => $params) {

            if (!$this->hasNewValue($attributeName)) {
                continue;
            }
            if (($attribute = ArrayHelper::getValue($params, 'attribute')) === null) {
                throw new ExitException("This attribute is not a found");
            }
            $values = $this->getNewValue($attributeName);
            $owner->{$attribute} = $values;
        }
    }

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param $attributeName
     * @return null
     */
    private function hasNewValue($attributeName)
    {
        return isset($this->_values[$attributeName]);
    }

    /**
     * Call user function
     * @param $function
     * @param $value
     * @return mixed
     * @throws ErrorException
     */
    private function callUserFunction($function, $value)
    {
        if (!is_array($function) && !$function instanceof \Closure) {
            throw new ErrorException("This value is not a function");
        }

        return call_user_func($function, $value);
    }


    public function callGetterMethod($type, $value, $params)
    {
        if ($type == self::METHOD_JSON) {
            return \yii\helpers\Json::decode($value);
        }
        if ($type == self::METHOD_STRING) {
            $delimiter = ArrayHelper::getValue($params, 'delimiter', ',');
            return explode($delimiter, $value);
        }
        return $value;
    }

    public function callSetterMethod($type, $value, $params)
    {
        if ($type == self::METHOD_JSON) {
            return \yii\helpers\Json::encode($value);
        }
        if ($type == self::METHOD_STRING) {
            $delimiter = ArrayHelper::getValue($params, 'delimiter', ',');
            return implode($delimiter, $value);
        }
        return $value;
    }




    /**
     * Returns a value indicating whether a property can be read.
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     * TODO: Make it honor $checkVars ??
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->params) ?
            true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * Returns a value indicating whether a property can be set.
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->params) ?
            true : parent::canSetProperty($name, $checkVars);
    }

    /**
     * Get parameters
     * @param $attributeName
     * @return mixed
     * @throws ErrorException
     */
    private function getParams($attributeName)
    {
        if (empty($this->params[$attributeName])) {
            throw new ErrorException("Parameter \"{$attributeName}\" does not exist");
        }

        return $this->params[$attributeName];
    }


    /**
     * Get value of a dirty attribute by name
     * @param $attributeName
     * @return null
     */
    private function getNewValue($attributeName)
    {
        return $this->_values[$attributeName];
    }


    /**
     * Sets the value of a component property. The data is passed
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @see __get()
     */
    public function __set($name, $value)
    {
        $params = $this->getParams($name);
        $method = ArrayHelper::getValue($params, 'method');

        if (!empty($params['set'])) {
            $this->_values[$name] = $this->callUserFunction($params['set'], $value);
        } else if ($method !== null) {
            $this->_values[$name] = $this->callSetterMethod($method, $value, $params);
        } else {
            $this->_values[$name] = $value;
        }
    }


    /**
     * Returns the value of an object property.
     * Get it from our local temporary variable if we have it,
     *
     * @param string $name the property name
     * @return mixed the property value
     * @see __set()
     */
    public function __get($name)
    {
        $params = $this->getParams($name);
        $value = null;
        if ($this->hasNewValue($name)) {
            $value = $this->getNewValue($name);
        }
        $method = ArrayHelper::getValue($params, 'method');

        if (!empty($params['get'])) {
            return $this->callUserFunction($params['get'], $value);
        } else if ($method !== null) {
            return $this->callGetterMethod($method, $value, $params);
        }

        return $value;
    }

}
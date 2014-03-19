<?php
namespace CLMVC\Core;

use CLMVC\Interfaces\IOptions;
/**
 * Class Options
 */
class Options
{
    /**
     * @var $pairs Option[]
     */
    private $pairs=array();
    /**
     * @var $namespace string
     */
    private $namespace;
    private $ioptions;
    private $initialized=false;

    /**
     * @param $namespace
     * @param IOptions $iOptions
     */
    public function __construct($namespace,IOptions $iOptions=NULL) {
        $this->namespace=$namespace;
        if($iOptions)
            $this->ioptions=$iOptions;
        else
            $this->ioptions=Container::instance()->make('CLMVC\\Interfaces\\IOptions',array($namespace));
    }

    /**
     * @param Option $option
     */
    public function add(Option $option) {
        if($this->initialized){
            $old=$this->pairs[$option->getKey()];
            $option->setValue($old->getValue());
            $this->pairs[$option->getKey()]=$option;
        }else
            $this->pairs[$option->getKey()]=$option;
    }

    /**
     * Return Option
     * @param $key
     * @return null|Option
     */
    public function get($key) {
        /**
        * @var Option $option
        */
        if(!isset($this->pairs[$key]))
            return null;
        return $this->pairs[$key];
    }

    /**
     * Set value for key
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setValue($key, $value) {
        $exists=false;
        if(!isset($this->pairs[$key])){
            $this->pairs[$key]=new Option($key,$value);
            $exists= true;
        }
        return $exists;
    }

    /**
     * Get value from key
     * @param string $key
     * @return null|mixed
     */
    public function getValue($key) {
        if(!isset($this->pairs[$key]))
            return null;
        return $this->pairs[$key]->getValue();
    }

    /**
     * Update an option
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function updateValue($key, $value) {
        if(isset($this->pairs[$key])){
            $this->pairs[$key]->setValue($value);
            return true;
        }
         return false;
    }

    /**
     * Check if option key exists
     * @param string $key
     * @return bool
     */
    public function exists($key) {
        return isset($this->pairs[$key]);
    }

    /**
     * Remove an option
     * @param string $key
     * @return bool
     */
    public function remove($key) {
        $exists=false;
        if(isset($this->pairs[$key])){
            unset($this->pairs[$key]);
            $exists=true;
        }
        return $exists;
    }

    /**
     * Delete options
     */
    public function delete() {
        $this->ioptions->delete($this->namespace);
        unset($this->pairs);
    }

    /**
     * Reset options to default values
     */
    public function reset() {
        /**
         * @var $key string
         * @var $option Option
         */
        foreach($this->pairs as $key => $option){
            $this->pairs[$key]=$option->reset();
        }
    }

    /**
     * Save options
     */
    public function save() {
        $this->init();
        $options=array();
        /**
         * @var Option $option
         */
        foreach($this->pairs as $option)
            $options[$option->getKey()]=$option->getValue();
        $this->ioptions->save($this->namespace,$options);
    }

    /**
     * Initiate Options by loading them
     */
    public function init() {
        if(!$this->initialized){
            $options=$this->ioptions->load($this->namespace);
            if(!empty($options))
            foreach($options as $key => $value){
                if(isset($this->pairs[$key]))
                    $this->pairs[$key]->setValue($value);
                else
                    $this->pairs[$key]=new Option($key,$value);
            }
            $this->initialized=true;
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key) {
        if(isset($this->pairs[$key]))
            return $this->pairs[$key]->getValue();
        else
            trigger_error("$key key does not exist",E_USER_WARNING );
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key,$value) {
        $this->pairs[$key]->setValue($value);
    }
    /**
     * @deprecated
     */
    public function isEmpty(){
        return sizeof($this->pairs)==0;
    }

    /**
     * @return array
     */
    public function getArray(){
        $options=array();
        foreach($this->pairs as $key => $option)
            $options[$key]=$option->getValue();
        return $options;
    }
}
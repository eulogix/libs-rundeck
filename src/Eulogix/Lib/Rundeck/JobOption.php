<?php

/*
 * This file is part of the Eulogix\Lib package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eulogix\Lib\Rundeck;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class JobOption {

    /**
     * @var string
     */
    private $name, $regex, $description, $jsonUrl, $defaultValue;

    /**
     * @var string[]
     */
    private $allowedValues;

    /**
     * @var boolean;
     */
    private $enforcedValues, $required=false, $isMultiValued=false;


    public function getBashPlaceHolder() {
        return '$RD_OPTION_'.strtoupper(preg_replace('/[^a-zA-Z0-9]/sim','_',$this->getName()));
    }

    /**
     * @return string
     */
    public function getXML()
    {
        $xml = "<option name='".$this->getName()."'";
        if($v = $this->getDefaultValue())
            $xml.=" value='$v'";
        if($v = $this->getRegex())
            $xml.=" regex='$v'";
        if($v = $this->getRequired())
            $xml.=" required='true'";
        if($v = $this->getAllowedValues())
            $xml.=" values='".implode(',',$v)."'";
        if($v = $this->getEnforcedValues())
            $xml.=" enforcedvalues='true'";
        if($v = $this->getIsMultiValued())
            $xml.=" multivalued='true' delimiter=','";
        $xml.="><description>".$this->getDescription()."</description></option>";

        return $xml;
    }

    /**
     * @param \string[] $allowedValues
     * @return $this
     */
    public function setAllowedValues($allowedValues)
    {
        $this->allowedValues = $allowedValues;

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getAllowedValues()
    {
        return $this->allowedValues;
    }

    /**
     * @param string $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }


    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param boolean $enforcedValues
     * @return $this
     */
    public function setEnforcedValues($enforcedValues)
    {
        $this->enforcedValues = $enforcedValues;

        return $this;
    }

    /**
     * @param boolean $isMultiValued
     * @return $this
     */
    public function setIsMultiValued($isMultiValued)
    {
        $this->isMultiValued = $isMultiValued;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsMultiValued()
    {
        return $this->isMultiValued;
    }


    /**
     * @return boolean
     */
    public function getEnforcedValues()
    {
        return $this->enforcedValues;
    }

    /**
     * @param boolean $required
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param string $jsonUrl
     * @return $this
     */
    public function setJsonUrl($jsonUrl)
    {
        $this->jsonUrl = $jsonUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getJsonUrl()
    {
        return $this->jsonUrl;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $regex
     * @return $this
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

}
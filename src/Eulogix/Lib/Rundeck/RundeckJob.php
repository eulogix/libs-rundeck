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

class RundeckJob {
    /**
     * @var string
     */
    private $id, $project, $name, $scriptContent, $description, $group;

    /**
     * @var boolean
     */
    private $multipleExecutions = false;

    /**
     * @var JobOption[]
     */
    private $options = [];

    /**
     * @param bool $asJobList
     * @return string
     */
    public function getXML($asJobList=false) {
        $xml = "<job>
                    <id>".$this->getId()."</id>
                    <loglevel>INFO</loglevel>
                    <multipleExecutions>".($this->getMultipleExecutions()?'true':'false')."</multipleExecutions>
                    <sequence keepgoing='false' strategy='node-first'>
                      <command>
                        <scriptargs />
                        <script><![CDATA[".$this->getScriptContent()."]]></script>
                      </command>
                    </sequence>
                    <description><![CDATA[".$this->getDescription()."]]></description>
                    <name>".$this->getName()."</name>
                    <context>
                      <project>".$this->getProject()."</project>
                      <options>";

        foreach($this->getOptions() as $option) {
            $xml .= $option->getXML();
        }

        $xml .= "</options>
                </context>
                <uuid>" . $this->getId() . "</uuid>
                <group>" . $this->getGroup() . "</group>
            </job>";

        $ret = $asJobList ? "<joblist>$xml</joblist>" : $xml;

        //pretty prints output
        $dd = new \DOMDocument();
        $dd->formatOutput=true;
        $dd->preserveWhiteSpace = false;
        $dd->loadXML($ret);

        return $dd->saveXml();
    }

    /**
     * @param string $project
     * @return $this
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
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
     * @param string $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $multipleExecutions
     * @return $this
     */
    public function setMultipleExecutions($multipleExecutions)
    {
        $this->multipleExecutions = $multipleExecutions;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getMultipleExecutions()
    {
        return $this->multipleExecutions;
    }

    /**
     * @param JobOption[] $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param JobOption $option
     * @return $this
     */
    public function addOption(JobOption $option)
    {
        $this->options[] = $option;
        return $this;
    }

    /**
     * @return JobOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $scriptContent
     * @return $this
     */
    public function setScriptContent($scriptContent)
    {
        $this->scriptContent = $scriptContent;
        return $this;
    }

    /**
     * @return string
     */
    public function getScriptContent()
    {
        return $this->scriptContent;
    }
} 
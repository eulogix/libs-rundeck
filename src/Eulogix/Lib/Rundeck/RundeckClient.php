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

class RundeckClient {

    /**
     * @var string
     */
    private $url, $token, $project;

    private $acceptHeaders = 'text/plain';

    public function __construct($httpUrl, $authToken, $project) {
        $this->url = $httpUrl;
        $this->token = $authToken;
        $this->setProject($project);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSystemInfo() {
        return $this->fetchRaw('/api/1/system/info');
    }

    /**
     * @return array
     */
    public function getJobs() {
        $this->setOutputFormatXML();
        $arr = $this->fetchArray("/api/2/project/{$this->getProject()}/jobs");
        return $this->parseJobs($arr);
    }

    /**
     * @param string $jobName
     *
     * @return int|bool
     */
    public function getJobIdByName($jobName) {
        $jobs = $this->getJobs();
        foreach($jobs as $jobId => $job) {
            if($job['name'] == $jobName) {
                return $jobId;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getProjects() {
        $this->setOutputFormatJson();
        $arr = $this->fetchArray("/api/1/projects");
        return $arr;
    }

    public function runJob($jobId, $arguments=[]) {

        $argString = '';
        foreach($arguments as $argName=>$argValue) {
            $argString.="-$argName \"". str_replace('"','""', $argValue). "\" ";
        }

        $this->setOutputFormatXML();
        $arr = $this->fetchArray("/api/1/job/$jobId/run", [
                'argString' => $argString
            ]);
        return $this->parseExecutions($arr);
    }

    public function getExecutionWithProgress($executionId) {
        $exec = $this->getExecution($executionId);
        $outputLines = 3;

        foreach($exec as &$execution) {
            if(@$execution['date-ended']) {
                $execution['percentCompletedOnAverageDuration'] = 100;
                $execution['percentCompletedOnOutputAnalysis'] = 100;

                if(count(@$execution['failedNodes'])>0) {
                    $outputLines = 1000;
                }

                $execution['tail'] = $this->getExecutionOutput($executionId, ['lastlines'=>$outputLines]);
            } else {
                $dateStart = new \DateTime($execution['date-started']);
                $now = new \DateTime();
                $tail = $this->getExecutionOutput($executionId, ['lastlines'=>$outputLines]);

                if($avgDuration = @$execution['job']['averageDuration']) {
                    $avgDuration = $avgDuration/1000;
                    $diff = $now->getTimestamp()-$dateStart->getTimestamp();
                    $execution['percentCompletedOnAverageDuration'] = min( floor(100*$diff/$avgDuration), 100);
                } else $execution['percentCompletedOnAverageDuration'] = 0;

                $progress = null;
                $tailLines = explode("\n", $tail);
                foreach($tailLines as $line) {
                    if(preg_match('/\[PROGRESS: ([0-9]+)%\]/im', $line, $m))
                        $progress = $m[1];
                }
                $execution['percentCompletedOnOutputAnalysis'] = $progress ? intval($progress) : 0;
            }
        }

        return $exec;
    }

    public function getExecution($executionId) {
        $this->setOutputFormatXML();
        $arr = $this->fetchArray("/api/1/execution/$executionId");
        return $this->parseExecutions($arr);
    }

    public function getExecutionOutput($executionId, $parameters=[]) {
        $this->setOutputFormatText();
        $output = $this->fetchRaw("/api/5/execution/$executionId/output", $parameters);
        return $output;
    }

    public function importJobs($jobListXml) {
        $this->setOutputFormatXML();
        $params = [
            'dupeOption'=>'update',
            'uuidOption'=>'remove',
            'project'=>$this->getProject(),
            'xmlBatch'=>$jobListXml,
        ];
        $arr = $this->fetchArray("/api/14/jobs/import", $params, ['x']);
        return $arr;
    }

    /**
     * @param $url
     * @param array $parameters
     * @param array $postData
     * @return array
     * @throws \Exception
     */
    public function fetchArray($url, $parameters=[], $postData=[]) {
        $raw = $this->fetchRaw($url, $parameters, $postData);

        if($xml = @simplexml_load_string($raw)) {
            return SimpleXMLElement2array($xml);
        }

        $jsonDec = json_decode($raw, true);
        if($jsonDec !== null)
            return $jsonDec;

        throw new \Exception( 'Unable to parse '.$raw );
    }

    /**
     * @param $url
     * @param array $parameters
     * @param array $postData
     * @return string
     * @throws \Exception
     */
    public function fetchRaw($url, $parameters=[], $postData=[]) {
        $c = $this->getCurl($postData ? ['Content-Type: application/x-www-form-urlencoded']:[]);
        $query = http_build_query($parameters);

        curl_setopt ($c, CURLOPT_URL, $this->url.$url.($query?"?$query":''));
        curl_setopt ($c, CURLINFO_HEADER_OUT ,1);
        curl_setopt ($c, CURLOPT_VERBOSE, 1);
        curl_setopt ($c, CURLOPT_HEADER ,1);

        if($postData) {
            curl_setopt ($c, CURLOPT_POST, 1);
            curl_setopt ($c, CURLOPT_POSTFIELDS, $postData);
        } else {
            curl_setopt ($c, CURLOPT_POSTFIELDS, null);
            curl_setopt ($c, CURLOPT_POST, 0);
        }

        $response = curl_exec ($c);
        if(!$response) {
            throw new \Exception(curl_error( $c ));
        }

        $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);

        $body = substr($response, $headerSize);

        if($xml = @simplexml_load_string($body)) {
            if($xml->attributes()['error']) {
                echo $body;
                var_dump(debug_backtrace());
                throw new \Exception( 'Rundeck API URL: '.$url.' Error message: '.$xml->error->message );
            }
        }

        return $body;
    }

    /**
     * @param array $header
     * @return resource
     */
    private function getCurl($header=[]) {

        $header[]  = 'X-Rundeck-Auth-Token: '.$this->token;
        $header[]  = 'Accept: '.$this->acceptHeaders;

        $ch = curl_init($this->url);
        curl_setopt ($ch, CURLOPT_USERAGENT, "Cool PHP Rundeck Client");
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt ($ch, CURLOPT_ENCODING, '');
        curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS , 5000);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
        return $ch;
    }

    /**
     * parses a simpleXmlconverted array in a readable and less nested array of jobs
     * @param $jobsRawArray
     * @return array
     */
    private function parseJobs($jobsRawArray) {
        $jobs = [];
        foreach($jobsRawArray['jobs'][0]['job'] as $job) {
            $job = $this->parseJob($job);
            $jobs[ $job['id'] ] = $job;
        }
        return $jobs;
    }

    private function parseJob($jobArray) {
        $attributes = $jobArray['@attributes'];
        unset($jobArray['@attributes']);
        return array_merge($attributes, $jobArray);
    }

    /**
     * parses a simpleXmlconverted array in a readable and less nested array of jobs
     * @param $executionsRawArray
     * @return array
     */
    private function parseExecutions($executionsRawArray) {
        $executions = [];
        foreach($executionsRawArray['executions'][0]['execution'] as $execution) {

            $attributes = $execution['@attributes'];
            unset($execution['@attributes']);

            $execution['job'] = $this->parseJob($execution['job'][0]);

            $executions[ $attributes['id'] ] = array_merge($attributes, $execution);
        }
        return $executions;
    }

    private function setOutputFormatText() {
        $this->acceptHeaders = "text/plain";
    }

    private function setOutputFormatXML() {
        $this->acceptHeaders = "text/xml";
    }

    private function setOutputFormatJson() {
        $this->acceptHeaders = "application/json";
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
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

} 
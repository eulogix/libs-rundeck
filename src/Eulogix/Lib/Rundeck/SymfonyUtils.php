<?php

/*
 * This file is part of the Eulogix\Cool package.
 *
 * (c) Eulogix <http://www.eulogix.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace Eulogix\Lib\Rundeck;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Pietro Baricco <pietro@eulogix.com>
 */

class SymfonyUtils {

    /**
     * @var string
     */
    protected $commandUser, $appPath;

    public function __construct($commandUser, $appPath) {
        $this->commandUser = $commandUser;
        $this->appPath = $appPath;
    }

    /**
     * @param Command $command
     * @param bool $includeDefaultOptions
     * @return RundeckJob
     */
    public function getRundeckJob(Command $command, $includeDefaultOptions=false) {

        $j = new RundeckJob();
        $j->setId($this->getUUID($command));
        $j->setName( $command->getName() );
        $j->setDescription( $command->getDescription() );

        $setupScript = '';
        $scriptContent = "cd {$this->appPath}\ncmd_string=\"sudo -u {$this->commandUser} php console {$command->getName()}";

        $args = $command->getDefinition()->getArguments();
        foreach($args as $arg)
            if($arg->getName()!='command') {
                $option = $this->getJobOptionFromInputArgument($command, $arg);
                $j->addOption($option);

                $scriptContent.=" ".$this->quoteBashVariable($option->getBashPlaceHolder());
            }

        $options = $command->getDefinition()->getOptions();
        array_push($options, new InputOption('env', null, InputOption::VALUE_OPTIONAL, 'Symfony env'));

        foreach($options as $opt)
            if(!$this->isOptionDefault($opt->getName()) || $includeDefaultOptions) {
                $option = $this->getJobOptionFromInputOption($command, $opt);
                $j->addOption($option);

                if($opt->isValueRequired()) {
                    $scriptContent.=" --{$opt->getName()} ".$this->quoteBashVariable($option->getBashPlaceHolder());
                } else {

                    $varName = 'opt_'.preg_replace('/[^a-zA-Z]/sim', '', $opt->getName());
                    $setupScript.="
{$varName}=''
if [ \"{$option->getBashPlaceHolder()}\" != '' ]; then
    {$varName}=\"--{$opt->getName()} ".($opt->acceptValue() ? $this->quoteBashVariable($option->getBashPlaceHolder()) : '')."\"
fi\necho \${$varName}\n";
                    $scriptContent.=" \${$varName}";
                }
            }

        $j->setScriptContent( $setupScript."\n$scriptContent\"
            echo \$cmd_string
            eval \$cmd_string
            ret_code=\$?
            echo \$ret_code
            exit \$ret_code" );

        return $j;
    }

    /**
     * @param Command $command
     * @param $arg
     * @return JobOption
     */
    protected function getJobOptionFromInputArgument(Command $command, InputArgument $arg)
    {
        $jobOption = new JobOption();
        $jobOption->setName($arg->getName());
        $jobOption->setDescription($arg->getDescription());
        $jobOption->setDefaultValue($arg->getDefault());
        $jobOption->setRequired($arg->isRequired());

        return $jobOption;
    }

    /**
     * @param Command $command
     * @param $option
     * @return JobOption
     */
    protected function getJobOptionFromInputOption(Command $command, InputOption $option)
    {
        $jobOption = new JobOption();
        $jobOption->setName($option->getName());
        $jobOption->setDescription($option->getDescription());
        $jobOption->setDefaultValue($option->getDefault());
        $jobOption->setRequired($option->isValueRequired());

        return $jobOption;
    }
    
    /**
     * @param string $var
     * @return string
     */
    protected function quoteBashVariable($var) {
        return "\\\"\\{$var}\\\"";
    }

    /**
     * @param Command $command
     * @return int
     */
    protected function getUUID(Command $command) {
        return crc32($command->getName());
    }

    /**
     * @param string $option
     * @return bool
     */
    protected function isOptionDefault($option) {
        return in_array($option, [
                'help',
                'quiet',
                'verbose',
                'version',
                'ansi',
                'no-ansi',
                'no-interaction',
                'no-debug',
                //'env',
                'process-isolation',
                'shell']);
    }

} 
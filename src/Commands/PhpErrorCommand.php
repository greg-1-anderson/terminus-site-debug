<?php
/**
 * This command will parse php-error.log on all appservers of a specific environment
 * specially on plans that has multiple appservers on live and test.
 */

namespace Pantheon\TerminusSiteLogs\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Pantheon\Terminus\Commands\Remote\DrushCommand;

/**
 * Class PhpErrorCommand
 * @package Pantheon\TerminusSiteLogs\Commands
 */
class PhpErrorCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * @var
     */
    private $site;

    /**
     * @var
     */
    private $environment;

    /**
     * @var string
     */
    private $logPath;

    /**
     * Object constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->logPath = getenv('HOME') . '/.terminus/site-logs';
    }

    /**
     * Parse php-error.log.
     * 
     * @command logs:parse:php-error
     * @aliases lp:php-error lp:pe
     * 
     * @param string $site_env The site name and site environment. Example: foo.dev for Dev environment, foo.test for Test environment, and foo.live for Live environment.
     * @option php Parse the logs via PHP. 
     * @option shell Parse the logs using *nix built-in tools.
     * @option newrelic Shows NewRelic summary report.
     * @option filter Equivalent to "head -N" where "N" is a numeric value. By default the value is 10 which will return the latest 10 entries.
     * 
     * @usage <site>.<env> --grouped-by="{KEYWORD}"
     * 
     * Search for the latest entries.
     *   terminus logs:parse:php-error <site>.<env> --grouped-by=latest 
     */ 
    public function ParsePhpErrorCommand($site_env, $options = ['php' => false, 'shell' => true, 'newrelic' => false, 'grouped-by' => '', 'uri' => '', 'filter' => 100, 'since' => '', 'until' => '', 'method' => ''])
    {
        // Get the site name and environment.
        $this->DefineSiteEnv($site_env);
        $site = $this->site->get('name');
        $env = $this->environment->id;

        if ($this->logPath . '/' . $site . '/' . $env)
        {
            $this->LogParser($site_env, $options);
            
            if ($options['newrelic'])
            {
                $this->output()->writeln('');
                $this->output()->writeln('Fetching NewRelic data.....');
                $this->output()->writeln('');
                $this->NewRelicHealthCheck($site_env);
            }
            exit();
        }

        $this->log()->error("No data found. Please run <info>terminus logs:get $site.$env</> command.");
    }

    /**
     * Log parser.
     */
    private function LogParser($site_env, $options) 
    {
        // Define site and environment.
        $this->DefineSiteEnv($site_env);
        $site = $this->site->get('name');
        $env = $this->environment->id;

        // Get the logs per environment.
        $dirs = array_filter(glob($this->logPath . '/' . $site . '/' . $env . '/*'), 'is_dir');

        $container = [];
        $storage = [];

        foreach ($dirs as $dir) 
        {
            if (file_exists($dir . '/php-error.log'))
            {
                // Parse php-error.log using *nix commands.
                if (!$options['php'] && $options['shell'])
                {
                    $this->ParsePhpErrorLog($dir, $options);
                }
                
                // Parse php-error.log using PHP.
                if ($options['php'] && $options['shell'])
                {
                    $container[] = $this->PhpParser($dir, $options, $storage);
                }
            }
        }
        print_r($container);
        exit();
        $this->PhpParserResult($container);
    }

    /**
     * Parse PHP slow logs.
     */
    private function ParsePhpErrorLog($dir, $options)
    {
        if (!$options['php'] && $options['shell'])
        {
            if (('which cat') && ('which grep') && ('which tail') && ('which cut') && ('which uniq') && ('which sort'))
            {
                $php_error_log = $dir . '/php-error.log';

                $this->output()->writeln("From <info>" . $php_error_log . "</> file.");
                
                switch ($options['grouped-by'])
                {
                    case 'latest':
                        $this->passthru("cat $php_error_log | tail -{$options['filter']}");
                        break;
                    default:
                        $this->log()->notice("You've reached the great beyond.");
                }
            } 
            else 
            {
                $this->log()->error("Required utilities are not installed.");
            }
        }
    }

    /**
     * PHP parser.
     */
    protected function PhpParser($dir, $options, $storage)
    {
        if ($options['php'] && $options['shell'])
        {
            $log = $dir . '/php-error.log';
            $handle = fopen($log, 'r');

            if ($handle) 
            {
                while (!feof($handle)) 
                {
                    $buffer = fgets($handle);
    
                    if (!empty($options['since']))
                    {
                        if (strpos($buffer, $options['filter']) !== FALSE && strpos($buffer, $options['since'])) 
                        {
                            $storage[][] = $buffer;
                        }
                    }
                    else 
                    {
                        if (strpos($buffer, $options['filter']) !== FALSE) 
                        {
                            $storage[][] = $buffer;
                        }
                    }
                }
                fclose($handle);
            }

            echo "hello";
            print_r($storage);
            echo "end";

            return $storage;
        }
    }

    /**
     * PHP parser result.
     */
    protected function PhpParserResult($container)
    {
        if (is_array(@$container)) 
        {
            $count = [];

            foreach ($container as $i => $matches) 
            {
                $this->output()->writeln("From <info>" . $i . "</> file.");
                $this->output()->writeln($this->line('='));
                
                foreach ($matches as $match)
                {
                    $count[] = $match;
                    $this->output()->writeln($match);
                    $this->output()->writeln($this->line('-'));
                }
            }
            $this->log()->notice(sizeof($count) . " " . ((sizeof($count) > 1) ? 'results' : 'result') . " matched found.");
        }
        else 
        {
            $this->log()->notice("No matches found.");
        }
    }

    /** 
     * Define site environment properties.
     * 
     * @param string $site_env Site and environment in a format of <site>.<env>.
     */
    private function DefineSiteEnv($site_env)
    {
        [$this->site, $this->environment] = $this->getSiteEnv($site_env);
    }

    /**
     * Passthru command. 
     */
    protected function passthru($command)
    {
        $result = 0;
        passthru($command, $result);

        if ($result != 0) 
        {
            throw new TerminusException('Command `{command}` failed with exit code {status}', ['command' => $command, 'status' => $result]);
        }
    }
}

<?php 
namespace WHMCS\Installer\Composer;


class MonologBufferedOutputWrapper extends \Symfony\Component\Console\Output\BufferedOutput
{
    protected $logger = NULL;

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    protected function doWrite($message, $newline)
    {
        $message = trim($message);
        if( $message ) 
        {
            if( $logger = $this->getLogger() ) 
            {
                try
                {
                    $logger->debug(strip_tags($message));
                }
                catch( \Exception $e ) 
                {
                    parent::doWrite("Logger error: " . $e->getMessage(), $newline);
                }
            }

            parent::doWrite($message, $newline);
        }

    }

}



<?php 
namespace WHMCS\Http\Message;


abstract class AbstractViewableResponse extends \Zend\Diactoros\Response\HtmlResponse
{
    protected $getBodyFromPrivateStream = false;

    public function __construct($data = "", $status = 200, array $headers = array(  ))
    {
        parent::__construct($data, $status, $headers);
    }

    public function getBody()
    {
        if( $this->getBodyFromPrivateStream ) 
        {
            return parent::getBody();
        }

        $body = new \Zend\Diactoros\Stream("php://temp", "wb+");
        $body->write($this->getOutputContent());
        $body->rewind();
        return $body;
    }

    abstract protected function getOutputContent();

}



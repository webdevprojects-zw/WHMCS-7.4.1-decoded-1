<?php 
namespace WHMCS\View\Markup\Error\Message\MatchDecorator;


abstract class AbstractMatchDecorator implements MatchDecoratorInterface
{
    use \WHMCS\View\Markup\Error\ErrorLevelTrait;

    protected $parsedMessages = NULL;
    private $data = NULL;

    public function __construct(\Iterator $data = NULL)
    {
        if( is_null($data) ) 
        {
            $data = new \SplDoublyLinkedList();
        }

        $this->parsedMessages = new \SplDoublyLinkedList();
        $this->data = $data;
    }

    abstract public function toPlain();

    abstract public function toHtml();

    abstract public function getTitle();

    abstract public function getHelpUrl();

    abstract protected function isKnown($data);

    public function wrap(\Iterator $data)
    {
        $matchDecorator = new static(clone $data);
        $matchDecorator->setParsedMessages($matchDecorator->getMatches());
        return $matchDecorator;
    }

    public function hasMatch()
    {
        return (bool) $this->getParsedMessages()->count();
    }

    public function getData()
    {
        return $this->data;
    }

    protected function setParsedMessages(\SplDoublyLinkedList $messages)
    {
        $this->parsedMessages = $messages;
        return $this;
    }

    public function getParsedMessages()
    {
        return $this->parsedMessages;
    }

    public function getParsedMessageList()
    {
        $messages = $this->getParsedMessages();
        $messages->rewind();
        $messageList = array(  );
        while( $messages->valid() ) 
        {
            $messageList[$messages->key()] = $messages->current();
            $messages->next();
        }
        return $messageList;
    }

    protected function getMatches()
    {
        $stack = $this->getData();
        $stack->rewind();
        $messages = new \SplQueue();
        while( $stack->valid() ) 
        {
            $msg = trim($stack->current());
            if( $this->isKnown($msg) ) 
            {
                $messages->enqueue($msg);
            }

            $stack->next();
        }
        return $messages;
    }

    protected function toGenericHtml($error = "")
    {
        $errorType = $this->errorName();
        return sprintf("<div class=\"updater-error-message-%s\"><strong>%s</strong>" . "&nbsp;(<a href=\"%s\" target=\"_blank\">Help Documentation</a>)<br>" . "%s: <em>%s</em></div>", strtolower($errorType), $this->getTitle(), $this->getHelpUrl(), $errorType, $error);
    }

    protected function toGenericPlain($error = "")
    {
        return sprintf("%s.\nHelp Documentation: %s\n%s: %s", $this->getTitle(), $this->getHelpUrl(), $this->errorName(), $error);
    }

    public function __toString()
    {
        return $this->toPlain();
    }

}



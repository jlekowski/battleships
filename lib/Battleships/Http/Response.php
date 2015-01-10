<?php

namespace Battleships\Http;

use Battleships\Misc;

/**
 * HTTP Response Class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
class Response
{
    /**
     * @var array
     */
    protected $headers = array(
        200 => "200 OK", // GET OK, PUT OK, DELETE OK
        201 => "201 Created", // POST OK
        204 => "204 No Content", // PUT OK Empty response
        404 => "404 Not Found" // GET No hash, PUT No hash, POST No hash, DELETE No hash
    );

    /**
     * @var string
     */
    protected $requestMethod;
    /**
     * @var mixed
     */
    protected $result;
    /**
     * @var \Exception
     */
    protected $error;

    /**
     * @param Request $oRequest
     */
    public function __construct(Request $oRequest)
    {
        ob_start();
        $this->requestMethod = $oRequest->getMethod();
    }

    public function __destruct()
    {
        ob_end_clean();
    }

    public function dispatch()
    {
        ob_get_clean();
        $restHeader = $this->getRestHeaders();
        if ($restHeader) {
            header('HTTP/1.1 ' . $restHeader);
        }
        header('Content-type: application/json');
        $formattedResponse = $this->getFormatted();
        if (!is_null($formattedResponse)) {
            echo json_encode($formattedResponse);
        }
        ob_flush();
    }

    /**
     * @param array|object|null $result
     * @throws \InvalidArgumentException
     */
    public function setResult($result)
    {
        if (!is_null($result) && !is_array($result) && !is_object($result)) {
            throw new \InvalidArgumentException(sprintf("Response result must be null, array, or object: %s given", gettype($result)));
        }

        $this->result = $result;
    }

    public function getRestHeaders()
    {
        return $this->hasError() ? $this->getHeaderForError() : $this->getHeaderForSuccess();
    }

    /**
     * @param \Exception $e
     */
    public function setError(\Exception $e)
    {
        Misc::log($e);
        $this->error = $e;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * @return array|\stdClass
     */
    public function getFormatted()
    {
        return $this->hasError() ? $this->getErrorFormatted() : $this->result;
    }

    /**
     * @return null|\stdClass
     */
    protected function getErrorFormatted()
    {
        $error = new \stdClass();
        $error->code = $this->error->getCode();
        $error->message = $this->error->getMessage();

        return $error;
    }

    /**
     * @return string
     */
    protected function getHeaderForSuccess()
    {
        $header = '';
        switch ($this->requestMethod) {
            case 'GET':
            case 'PUT':
            case 'DELETE':
                $header = $this->headers[200];
                break;

            case 'POST':
                $header = $this->headers[201];
                break;

            default:
                break;
        }

        return $header;
    }

    /**
     * @return string
     */
    protected function getHeaderForError()
    {
        $header = '';
        switch ($this->requestMethod) {
            case 'GET':
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $header = $this->headers[404];
                break;

            default:
                break;
        }

        return $header;
    }
}

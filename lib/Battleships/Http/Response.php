<?php

namespace Battleships\Http;

use Battleships\Misc;

/**
 * HTTP Response Class
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
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
    private $header;
    /**
     * @var string
     */
    private $requestMethod;
    /**
     * @var mixed
     */
    private $result;
    /**
     * @var \Exception
     */
    private $error;

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
        $this->applyRestHeaders();
        if ($this->header) {
            header('HTTP/1.1 ' . $this->header);
        }
        header('Content-type: application/json');
        echo json_encode($this->getFormatted());
        ob_flush();
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    public function applyRestHeaders()
    {
        $this->header = $this->hasError()
            ? $this->getHeaderForError()
            : $this->getHeaderForSuccess();
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
     * @return \stdClass
     */
    public function getFormatted()
    {
        $response = new \stdClass();
        $response->result = $this->result;
        $response->error = $this->getErrorFormatted();

        return $response;
    }

    /**
     * @return null|\stdClass
     */
    private function getErrorFormatted()
    {
        if (!$this->hasError()) {
            return null;
        }

        $error = new \stdClass();
        $error->code = $this->error->getCode();
        $error->message = $this->error->getMessage();

        return $error;
    }

    /**
     * @return string
     */
    private function getHeaderForSuccess()
    {
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
    private function getHeaderForError()
    {
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

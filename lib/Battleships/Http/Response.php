<?php

namespace Battleships\Http;

use Battleships\Misc;
use Battleships\Http\Request;

class Response
{
    protected $headers = array(
        200 => "200 OK", // GET OK, PUT OK, DELETE OK
        201 => "201 Created", // POST OK
        204 => "204 No Content", // PUT OK Empty response
        404 => "404 Not Found" // GET No hash, PUT No hash, POST No hash, DELETE No hash
    );

    private $header;
    private $requestMethod;
//    private $response;
    private $result;
    /**
     * @var \Exception
     */
    private $error;

    public function __construct(Request $oRequest)
    {
        $this->requestMethod = $oRequest->getMethod();
//        ob_start();
    }

    public function __destruct()
    {
//        ob_end_clean();
    }

//    public function __set($name, $value) {
//        throw new \InvalidArgumentException;
//    }

    public function dispatch()
    {
//        ob_get_clean();
        $this->applyRestHeaders();
        if ($this->header) {
            header('HTTP/1.1 ' . $this->header);
        }
        header('Content-type: application/json');
        echo json_encode($this->getFormatted());
//        ob_flush();
    }

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

    public function setError(\Exception $e)
    {
        Misc::log($e);
        $this->error = $e;
    }

    public function hasError()
    {
        return !empty($this->error);
    }

    public function getFormatted()
    {
        $response = new \stdClass();
        $response->result = $this->result;
        $response->error = $this->getErrorFormatted();

        return $response;
    }

    private function getErrorFormatted()
    {
        if (!$this->hasError()) {
            return $this->error;
        }

        $error = new \stdClass();
        $error->code = $this->error->getCode();
        $error->message = $this->error->getMessage();

        return $error;
    }

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

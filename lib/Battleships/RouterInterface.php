<?php

namespace Battleships;

/**
 * Router Interface
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */
interface RouterInterface
{
    /**
     * Get name of the controller
     * @return string
     */
    public function getControllerName();
    /**
     * Get name of the action
     * @return string
     */
    public function getActionName();
    /**
     * Get URL parameters with values;
     * @return array
     */
    public function getParams();
    /**
     * Get data passed in the HTTP Request (e.g. RAW POST in JSON format)
     * @return string
     */
    public function getData();
}

<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Routigo\Webservices\Endpoints;

interface EndpointInterface
{
    /**
     * @param null $requestData
     * @param bool $includeHttpStatus
     *
     * @return array|mixed|\Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function call($requestData = null, $includeHttpStatus = false);

    /**
     * @return string
     */
    public function getEndpointUrl();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param string $urlArguments
     */
    public function setUrlArguments($urlArguments);

    /**
     * @return array
     */
    public function getUrlArguments();

    /**
     * @param array $requestData
     */
    public function setRequestData(array $requestData);

    /**
     * @return array
     */
    public function getRequestData();
}

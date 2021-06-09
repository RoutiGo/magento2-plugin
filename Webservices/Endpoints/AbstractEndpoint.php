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
namespace TIG\RoutiGo\Webservices\Endpoints;

use TIG\RoutiGo\Webservices\Rest;

abstract class AbstractEndpoint implements EndpointInterface
{
    const METHOD = 'PUT';

    private $requestData = [];

    private $urlArguments;

    /** @var Rest */
    private $restApi;

    /**
     * @param Rest $restApi
     */
    public function __construct(Rest $restApi)
    {
        $this->restApi = $restApi;
    }

    /**
     * {@inheritDoc}
     */
    public function call($requestData = null, $includeHttpStatus = false)
    {
        $this->setRequestData($requestData);

        return $this->restApi->getRequest($this, $includeHttpStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function getEndpointUrl()
    {
        $endpointUrl = sprintf(static::ENDPOINT_URL);

        return $endpointUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {
        return static::METHOD;
    }

    /**
     * {@inheritDoc}
     */
    public function setUrlArguments($urlArguments)
    {
        $this->urlArguments = $urlArguments;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrlArguments()
    {
        return $this->urlArguments;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequestData(array $requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestData()
    {
        return $this->requestData;
    }
}

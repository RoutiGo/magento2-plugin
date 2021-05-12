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
namespace TIG\Routigo\Webservices;

use Magento\Framework\HTTP\ZendClient;
use TIG\Routigo\Model\Config\Provider\ApiConfiguration;
use TIG\Routigo\Config\Provider\General\Configuration;
use TIG\Routigo\Webservices\Endpoints\EndpointInterface;
//@codingStandardsIgnoreFile
class Rest
{
    /** @var ZendClient */
    protected $zendClient;

    /** @var ApiConfiguration */
    private $apiConfiguration;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param ZendClient       $zendClient
     * @param ApiConfiguration $apiConfiguration
     * @param Configuration    $configuration
     */
    public function __construct(
        ZendClient $zendClient,
        ApiConfiguration $apiConfiguration,
        Configuration $configuration
    ) {
        $this->zendClient = $zendClient;
        $this->apiConfiguration = $apiConfiguration;
        $this->configuration = $configuration;
    }

    /**
     * @param EndpointInterface $endpoint
     * @param bool              $includeHttpStatus
     *
     * @return array|\Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function getRequest(EndpointInterface $endpoint, $includeHttpStatus = false)
    {
        $this->zendClient->resetParameters(true);

        $this->setUri($endpoint->getEndpointUrl());
        $this->setHeaders();
        $this->setParameters($endpoint);
        $httpStatus = 0;

        try {
            $response = $this->zendClient->request();
            $httpStatus = $response->getStatus();
            $response = $this->formatResponse($response->getBody());
        } catch (\Zend_Http_Client_Exception $exception) {
            $response = [
                'success' => false,
                'error' => __('%1 : Zend Http Client exception', $exception->getCode())
            ];
        }

        if ($includeHttpStatus) {
            $response['http_status'] = $httpStatus;
        }

        return $response;
    }

    /**
     * @param string $endpointUrl
     *
     * @throws \Zend_Http_Client_Exception
     */
    private function setUri($endpointUrl)
    {
        $uri = $this->apiConfiguration->getModeApiBaseUrl() . $endpointUrl;

        $this->zendClient->setUri($uri);
    }

    /**
     * @throws \Zend_Http_Client_Exception
     * @throws \Exception
     */
    protected function setHeaders()
    {
        $this->zendClient->setHeaders([
            'X-API-Key' => $this->configuration->getKey(),
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * @param EndpointInterface $endpoint
     *
     * @throws \Zend_Http_Client_Exception
     */
    private function setParameters(EndpointInterface $endpoint)
    {
        $endpointMethod = $endpoint->getMethod();
        $endpointData = $endpoint->getRequestData();

        $this->zendClient->setMethod($endpointMethod);

        if (empty($endpointData)) {
            return;
        }

        switch ($endpointMethod) {
            case ZendClient::GET:
                $this->zendClient->setParameterGet($endpointData);
                break;
            case ZendClient::POST:
            case ZendClient::PUT:
            default:
                $this->zendClient->setRawData(json_encode($endpointData));
                break;
        }
    }

    /**
     * @param $response
     *
     * @return array
     */
    private function formatResponse($response)
    {
        if (is_string($response)) {
            $response = json_decode($response, true);
        }

        if (!is_array($response)) {
            $response = [$response];
        }

        return $response;
    }
}

<?php

namespace Ufo\JsonRpcBundle\Postman;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Zend\Json\Server\Smd\Service;


/**
 * Created by PhpStorm.
 * User: mouse
 * Date: 9/14/18
 * Time: 6:20 PM
 */
class CollectionGenerator {

    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Router
     */
    protected $router;
    /**
     * @var string
     */
    protected $env;
    /**
     * @var string
     */
    protected $tokenKey;

    /**
     * ProjectGenerator constructor.
     * @param RequestStack $requestStack
     * @param Router $router
     * @param string $environment
     * @param null $soaTokenKey
     */
    public function __construct(RequestStack $requestStack, Router $router, $environment = 'prod', $soaTokenKey = null)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->env = $environment;
        $this->tokenKey = $soaTokenKey;
    }

    protected $items = [];

    protected $root;

    public function addService(Service $procedure)
    {

        $headers = [];
        $headers[] = [
            "key"   => "Content-Type",
            "value" => "application/json",
        ];

        $headers[] = [
            "key"   => "{{Token_name}}",
            "value" => "{{Token_value}}",
        ];

        $params = [];
        $arrayParamsToFix = [];

        foreach ($procedure->getParams() as $param) {
            if (!key_exists('name', $param)) {
                continue;
            }
            $params[$param['name']] = "{{{$param['name']}}}";
            if($param['type'] == 'array') {
                $arrayParamsToFix[] = $params[$param['name']];
            }
        }
        $raw = [
            "id"     => "test_id",
            "method" => $procedure->getName(),
            "params" => $params,
        ];

        $fixedRawBody = json_encode($raw);
        if ($arrayParamsToFix) {
            $fixedRawBody = str_replace(array_map(function ($elem) {
                return sprintf('"%s"', $elem);
            }, $arrayParamsToFix), $arrayParamsToFix, $fixedRawBody);
        }

        $this->items[] = [
            'name'     => $procedure->getName(),
            'request'  => [
                'method' => $procedure->getTransport(),
                'header' => $headers,
                'body'   => [
                    "mode" => "raw",
                    "raw"  => $fixedRawBody,
                ],
                'url'    => [
                    "raw"      => "http://rt.loc:8082/api",
                    "protocol" => "http",
                    "host"     => [
                        "rt",
                        "loc",
                    ],
                    "port"     => "8082",
                    "path"     => [
                        "api",
                    ],
                ],
            ],
            'response' => ['test_response'],
        ];

//        var_dump(__FILE__ . ':' . __LINE__, $procedure->getName());die;
//        $interface = new RootNode();
    }

    public function createJson()
    {


        return json_encode([
                "info"  => [
                    "name"        => "rt2.0",
                    "_postman_id" => "0e08b83c-e37c-e54a-6586-e24274049f5e",
                    "description" => "",
                    "schema"      => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
                ],
                "items" => $this->items,
            ]
        );
//        $root = new RootNode([
//            'name' => $this->request->getHost()
//        ]);
//
//        return ArrayToXml::convert(
//            ISoupUiNode::SOUPUI_NS . $root->getTag(),
//            $this->getSoupUiTemplate(),
//            $root->getAttributes()
//        );
    }

}
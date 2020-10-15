<?php
namespace Orzcc\Opensearch\Sdk;
/**
 * Cloudsearch service。
 *
 * 此类主要提供一下功能：
 * 1、根据请求的参数来生成签名和nonce。
 * 2、请求API服务并返回response结果。
 *
 * @author guangfan.qu
 *
 */
class CloudsearchClient {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';

    const API_VERSION = '3';
    const API_TYPE = 'openapi';

    const SDK_VERSION = '3.3.0';
    const SDK_TYPE    = 'opensearch_sdk';

    private $debug = false;

    public $securityToken = false;

    public $timeout = 10;
    public $connectTimeout = 1;
    public $host;
    public $accessKey;
    public $secret;
    public $gzip;
    public $appname;

    /**
     * 构造方法。
     *
     * @param string $accessKey 指定您的accessKeyId，在 https://ak-console.aliyun.com/#/accesskey 中可以创建。
     * @param string $secret 指定您的secret。
     * @param string $host 指定您要访问的区域的endPoint，在控制台应用详情页中有指定。
     * @param array @options 指定一些可选参数，debug：true/false，是否开启debug模式（默认不开启），gzip:true/false 是否开启gzip压缩（默认不开启），timeout：超时时间，seconds（默认10秒）,connectTimeout: 连接超时时间，seconds(默认1秒)
     * @return void
     */
    public function __construct($accessKey, $secret, $host,$appname, $options = array()) {
        $args = array(
            'accessKey' => trim($accessKey),
            'secret' => trim($secret),
            'host' => trim($host),
            'name' => trim($appname),
            'options' => $options
        );

        if (isset($options['gzip'])) {
            $this->gzip = $options['gzip'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (isset($options['connectTimeout'])) {
            $this->connectTimeout = $options['connectTimeout'];
        }

        if (isset($options['securityToken'])) {
            $this->securityToken = $options['securityToken'];
        }

        if (isset($options['debug'])) {
            $this->debug = (boolean) $options['debug'];
        }
        $this->host = $host;
        $this->accessKey = $args['accessKey'];
        $this->secret = $args['secret'];
        $this->appname = $args['name'];
//        parent::__construct($args);
    }

    /**
     * 发送一个GET请求。
     *
     * @param string $uri 发起GET请求的uri。
     * @param array $params 发起GET请求的参数，以param_key => param_value的方式体现。
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function get($uri, $params = array()) {
        return $this->call($uri, $params, '', self::METHOD_GET);
    }

    /**
     * 发送一个PUT请求。
     *
     * @param string $uri 发起PUT请求的uri。
     * @param string $body 发起PUT请求的body体，为一个原始的json格式的string。
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function put($uri, $body = '') {
        return $this->call($uri, array(), $body, self::METHOD_PUT);
    }

    /**
     * 发送一个POST请求。
     *
     * @param string $uri 发起POST请求的uri。
     * @param string $body 发起POST请求的body体，为一个原始的json格式的string。
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function post($uri, $body = '') {
        return $this->call($uri, array(), $body, self::METHOD_POST);
    }

    /**
     * 发送一个DELETE请求。
     *
     * @param string $uri 发起DELETE请求的uri。
     * @param string $body 发起DELETE请求的body体，为一个原始的json格式的string。
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function delete($uri, $body = '') {
        return $this->call($uri, array(), $body, self::METHOD_DELETE);
    }

    /**
     * 发送一个PATCH请求。
     *
     * @param string $uri 发起PATCH请求的uri。
     * @param string $body 发起PATCH请求的body体，为一个原始的json格式的string。
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function patch($uri, $body = '') {
        return $this->call($uri, array(), $body, self::METHOD_PATCH);
    }

    /**
     * 发送一个请求。
     *
     * @param string $uri 发起请求的uri。
     * @param array $params 指定的url中的query string 列表。
     * @param string $body 发起请求的body体，为一个原始的json格式的string。
     * @param string $method 发起请求的方法，有GET/POST/DELETE/PUT/PATCH等
     * @return \OpenSearch\Generated\Common\OpenSearchResult
     */
    public function call($uri, array $params, $body, $method) {
        $path = "/v" . self::API_VERSION . "/" . self::API_TYPE ."/apps/{$this->appname}". "{$uri}";
        $url = $this->host . $path;

        $items = array();
        $items['method'] = $method;
        $items['request_path'] = $path;
        $items['content_type'] = "application/json";
        $items['accept_language'] = "zh-cn";
        $items['date'] = gmdate('Y-m-d\TH:i:s\Z');
        $items['opensearch_headers'] = array();
        $items['content_md5'] = $body==""?"":md5($body);
        $items['opensearch_headers']['X-Opensearch-Nonce'] = $this->_nonce();
        if ($this->securityToken) {
            $items['opensearch_headers']['X-Opensearch-Security-Token']
                = $this->securityToken;
        }

        if ($method != self::METHOD_GET) {
            if (!empty($body)) {
                $items['content_md5'] = md5($body);
                $items['body_json'] = $body;
            }
        }
        $items['query_params'] = $params;

        $signature = $this->_signature($this->secret, $items);
        $items['authorization'] = "OPENSEARCH {$this->accessKey}:{$signature}";

        return $this->_curl($url, $items);
    }

    private function _nonce() {
        return intval(microtime(true) * 1000) . mt_rand(10000, 99999);
    }

    private function _signature($secret, $items) {
        $params = isset($items['query_params']) ? $items['query_params'] : "";

        $signature = '';
        $string = '';
        $string .= strtoupper($items['method']) . "\n";
        $string .= $items['content_md5'] . "\n";
        $string .= $items['content_type'] . "\n";
        $string .= $items['date'] . "\n";

        $headers = self::_filter($items['opensearch_headers']);
        foreach($headers as $key => $value){
            $string .= strtolower($key) . ":" . $value."\n";
        }

        $resource = str_replace('%2F', '/', rawurlencode($items['request_path']));
        $sortParams = self::_filter($params);

        $queryString = $this->_buildQuery($sortParams);
        $canonicalizedResource = $resource;

        if(!empty($queryString)){
            $canonicalizedResource .= '?'.$queryString;
        }

        $string .= $canonicalizedResource;

        $signature = base64_encode(hash_hmac('sha1', $string, $secret, true));
        return $signature;
    }

    private function _buildQuery($params) {
        $query = '';
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $query = !empty($params) ? http_build_query($params, null, '&', PHP_QUERY_RFC3986) : '';
        } else {
            $arg = '';
            foreach ($params as $key => $val) {
                $arg .= rawurlencode($key) . "=" . rawurlencode($val) . "&";
            }
            $query = substr($arg, 0, count($arg) - 2);
        }

        return $query;
    }

    private function _filter($parameters = array()){
        $params = array();
        if(!empty($parameters)){
            foreach ($parameters as $key => $val) {
                if ($key == "Signature" ||$val === "" || $val === NULL){
                    continue;
                } else {
                    $params[$key] = $parameters[$key];
                }
            }

            uksort($params,'strnatcasecmp');
            reset($params);
        }
        return $params;
    }

    private function _getHeaders($items) {
        $headers = array();
        $headers[] = 'Content-Type: '.$items['content_type'];
        $headers[] = 'Date: '.$items['date'];
        $headers[] = 'Accept-Language: '.$items['accept_language'];
        $headers[] = 'Content-Md5: '.$items['content_md5'];
        $headers[] = 'Authorization: '.$items['authorization'];
        if (is_array($items['opensearch_headers'])) {
            foreach($items['opensearch_headers'] as $key => $value){
                $headers[] = $key . ": " . $value;
            }
        }

        return $headers;
    }

    private function _curl($url, $items) {
        $method = strtoupper($items['method']);
        $options = array(
            CURLOPT_HTTP_VERSION => 'CURL_HTTP_VERSION_1_1',
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "opensearch/php sdk " . self::SDK_VERSION . "/" . PHP_VERSION,
            CURLOPT_HTTPHEADER => $this->_getHeaders($items),
        );

        if ($method == self::METHOD_GET) {
            $query = $this->_buildQuery($items['query_params']);
            $url .= preg_match('/\?/i', $url) ? '&' . $query : '?' . $query;
        } else{
            if(!empty($items['body_json'])){
                $options[CURLOPT_POSTFIELDS] = $items['body_json'];
            }
        }

        if ($this->gzip) {
            $options[CURLOPT_ENCODING] = 'gzip';
        }

        if ($this->debug) {
            $out = fopen('php://temp','rw');
            $options[CURLOPT_VERBOSE] = true;
            $options[CURLOPT_STDERR] = $out;
        }

        $session = curl_init($url);
        curl_setopt_array($session, $options);
        $response = curl_exec($session);
        if ($this->debug) {
            $this->debugInfo = curl_getinfo($session);
        }
        curl_close($session);

        return $response;
    }
}

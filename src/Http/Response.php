<?php
namespace TypeRocket\Http;

use JsonSerializable;
use TypeRocket\Core\Config;
use TypeRocket\Database\Results;
use TypeRocket\Elements\Notice;
use TypeRocket\Models\Model;
use TypeRocket\Template\View;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Str;

/**
 * Class Response
 *
 * The Response class is not designed for PSR-7
 *
 * This class is designed to give hooks for the json
 * response sent back by the TypeRocket AJAX REST
 * API.
 *
 * IMPORTANT: This class is only registered to the
 * container when it is called with getFromContainer
 *
 * @package TypeRocket\Http
 */
class Response implements JsonSerializable
{
    public const ALIAS = 'response';

    protected $message = '';
    protected $messageType = 'success';
    protected $redirect = false;
    protected $status = null;
    protected $flash = true;
    protected $blockFlash = false;
    protected $lockFlash = false;
    protected $errors = [];
    protected $data = [];
    protected $cancel = false;
    protected $return;

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }

    /**
     * Get Return
     *
     * @return mixed
     */
    public function &getReturn()
    {
        return $this->return;
    }

    /**
     * Set Return
     *
     * @param $return
     * @return Response
     */
    public function setReturn(&$return)
    {
        $this->return = $return;

        return $this;
    }

    /**
     * Set HTTP status code
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus( $status )
    {
        $this->status = (int) $status;

        status_header( $status );

        return $this;
    }

    /**
     * Cancel Response
     *
     * @param bool $bool
     *
     * @return Response
     */
    public function setCancel($bool = true)
    {
        $this->cancel = (bool) $bool;

        return $this;
    }

    /**
     * Get Cancel
     *
     * @return bool
     */
    public function getCancel()
    {
        return $this->cancel;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function bad($message)
    {
        $this->setStatus(400);
        $this->setMessage($message, 'error');

        return $this;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function unauthorized($message)
    {
        $this->setStatus(401);
        $this->setMessage($message, 'error');

        return $this;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function forbidden($message)
    {
        $this->setStatus(403);
        $this->setMessage($message, 'error');

        return $this;
    }

    /**
     * @param string $message
     * @param int $code
     *
     * @return $this
     */
    public function success($message, $code = null)
    {
        $this->setStatus( $code ?? $this->getStatus() ?? 200);
        $this->setMessage($message, 'success');

        return $this;
    }

    /**
     * @param string $message
     * @param int $code
     *
     * @return $this
     */
    public function warning($message, $code = null)
    {
        $this->setStatus($code ?? $this->getStatus() ?? 200);
        $this->setMessage($message, 'warning');

        return $this;
    }

    /**
     * @param string $message
     * @param int $code
     *
     * @return $this
     */
    public function error($message, $code = null)
    {
        $this->setStatus($code ?? $this->getStatus() ?? 422);
        $this->setMessage($message, 'error');

        return $this;
    }

    /**
     * Set Header
     *
     * @param $name
     * @param $value
     * @return Response
     */
    public function setHeader($name, $value)
    {
        header("$name: $value");

        return $this;
    }

    /**
     * Set Headers
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Remove Header
     *
     * @param string $name
     * @return $this
     */
    public function removeHeader($name)
    {
        if($name && is_string($name)) {
            header_remove($name);
        }

        return $this;
    }

    /**
     * Get Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        $list = headers_list();
        $formatted = [];
        foreach ($list as $item) {
            [$key, $value] = explode(':', $item, 2);
            $formatted[strtolower($key)] = trim($value);
        }

        return $formatted;
    }

    /**
     * Set Last Modified Header
     *
     * Last-Modified: <day-name>, <day> <month> <year> <hour>:<minute>:<second> GMT
     *
     * @param int $utc_time UTC unix timestamp
     *
     * @return $this
     */
    public function setHeaderLastModified($utc_time)
    {
        return $this->setHeader('Last-Modified', (new \DateTime)->setTimestamp($utc_time)->format('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
     *
     * @param $value
     *
     * @return $this
     */
    public function setCacheControl($value)
    {
        return $this->setHeader('Cache-Control', $value);
    }

    /**
     * Do Not Cache
     *
     * Works for HTTP 1.1 only
     *
     * @return $this
     */
    public function noCache()
    {
        return $this->setHeaders([
            'Cache-Control' => 'no-store, must-revalidate',
            'Expires' => 0
        ]);
    }

    /**
     * Do not cache with TypeRocket Pro Rapid Pages extension
     *
     * @return $this
     */
    public function noRapidPagesCache()
    {
        return $this->setHeader( 'X-No-Cache', 'yes');
    }

    /**
     * Require Basic Authentication
     *
     * @param string $message
     * @param string $realm
     *
     * @return $this
     */
    public function basicAuthentication(string $message = 'Unauthorized', string $realm = 'TypeRocket Protected Area')
    {
        $realm = htmlentities($realm);
        $this->setHeader('WWW-Authenticate', "Basic realm=\"{$realm}\"");
        return $this->unauthorized($message);
    }

    /**
     * Does Response Need Basic Authentication
     *
     * If the response received PHP_AUTH_USER do not
     * send WWW-Authenticate header.
     *
     * @param string $message
     * @param string $realm
     *
     * @return bool
     */
    public function needsBasicAuthentication(string $message = 'Unauthorized', string $realm = 'TypeRocket Protected Area') : bool
    {
        if(empty($_SERVER['PHP_AUTH_USER'])) {
            $this->basicAuthentication($message, $realm);

            return true;
        }

        return false;
    }

    /**
     * Set Download Headers
     *
     * @param string $file full file path
     * @param string|null $name
     * @param array|null $headers
     * @param string|null $type
     *
     * @return $this
     */
    public function setDownloadHeaders($file, $name = null, ?array $headers = null, $type = null)
    {
        $type = $type ?? 'attachment';
        $name = $name ?? pathinfo($file, PATHINFO_BASENAME);
        $mime = File::new($file)->mimeType();
        $main = array_merge([
            'Content-Type' => $mime ?: 'application/octet-stream',
        ], $headers ?? [], [
            'Content-Disposition' =>  $type . '; filename="'.$name.'"',
        ]);

        $this->setHeaders($main);

        return $this;
    }

    /**
     * Set Response Content-type header
     *
     * @param string $name
     *
     * @return $this
     */
    public function send($name)
    {
        $charset =  get_option('blog_charset');

        $types = [
            'json' => 'application/json; charset=' .$charset,
            'json-ld' => 'application/ld+json',
            'xml' => 'application/xml',
            'html' => 'text/html; charset=' .$charset,
            't-xml' => 'text/xml',
            'plain' => 'text/pain',
        ];

        $name = $types[$name] ?? $name;
        header("Content-type: " . $name);

        return $this;
    }

    /**
     * Response send content type
     *
     * @param string $name
     *
     * @return bool
     */
    public function sends($name)
    {
        $headers = $this->getHeaders();

        $types = [
            'json' => 'application/json',
            'json-ld' => 'application/ld+json',
            'html' => 'text/html',
            'xml' => 'application/xml',
            't-xml' => 'text/xml',
            'plain' => 'text/pain',
            'image' => 'image/',
        ];

        $search = $types[$name] ?? $name;
        return $search ? Str::contains($search, $headers['content-type'] ?? '') : false;
    }

    /**
     * Set message property
     *
     * This is the message seen in the flash alert.
     *
     * @param string $message
     * @param null|string $type
     * @param bool $statusMatchType
     *
     * @return $this
     */
    public function setMessage($message, $type = null, $statusMatchType = true)
    {
        $this->message = $message;

        if($type) {
            $this->setMessageType($type, $statusMatchType);
        }

        return $this;
    }

    /**
     * Set Message Type
     *
     * @param string|null $type success, error, warning
     * @param bool $statusMatchType
     *
     * @return Response
     */
    public function setMessageType($type, $statusMatchType = true)
    {
        $this->messageType = strtolower($type ?? $this->messageType);

        if($statusMatchType && $this->messageType == 'error') {
            $this->setStatus($this->getStatus() ?? 422);
        }

        return $this;
    }

    /**
     * Get Message Type
     *
     * @return string|null
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Set redirect
     *
     * Redirect the user to a new url. This only works when using AJAX
     * REST API on a Form.
     *
     * @param string|Redirect $url
     *
     * @return $this
     */
    public function setRedirect( $url )
    {
        if($url instanceof Redirect) {
            $url = $url->getUrl();
        }

        $this->redirect = $url;

        return $this;
    }

    /**
     * Can Redirect
     *
     * Will redirect a TypeRocket AJAX request if a redirect is returned
     * from the controller.
     *
     * @return $this
     */
    public function canRedirect()
    {
        $this->redirect = $this->redirect ?: true;

        return $this;
    }

    /**
     * Set Flash
     *
     * Set if the flash message should be shown on the front end.
     *
     * @param bool|true $flash
     *
     * @return $this
     */
    public function setFlash( $flash = true )
    {
        $this->flash = (bool) $flash;

        return $this;
    }

    /**
     * Has Errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Set Errors
     *
     * Set errors to help front-end developers
     *
     * @param array $errors array must have keys
     *
     * @return $this
     */
    public function setErrors( $errors )
    {
        $this->errors = $errors;
        $this->setMessageType('error');

        return $this;
    }

    /**
     * Get Errors
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get Error by key
     *
     * @param string $key
     *
     * @return array
     */
    public function getError($key) {

        $error = null;

        if(array_key_exists($key, $this->errors)) {
            $error = $this->errors[$key];
        }

        return $error;
    }

    /**
     * Set Error by key
     *
     * @param string $key
     * @param string|array $value
     *
     * @return $this
     */
    public function setError($key, $value)
    {
        $this->errors[$key] = $value;
        $this->setMessageType('error');

        return $this;
    }

    /**
     * Remove Error by key
     *
     * @param string $key
     *
     * @return $this
     */
    public function removeError($key)
    {
        if(array_key_exists($key, $this->errors)) {
            unset($this->errors[$key]);
        }

        return $this;
    }

    /**
     * Set Data by key
     *
     * Set the data to return for front-end developers. This should
     * be data used to describe what was updated or created for
     * example.
     *
     * @param string $key
     * @param string|array $data
     *
     * @return Response
     */
    public function setData( $key, $data )
    {
        $this->data[$key] = $data;

        return $this;
    }

    /**
     * Get HTTP status
     *
     * @param bool $real
     *
     * @return int|null
     */
    public function getStatus($real = false)
    {
        if($real) {
            return http_response_code();
        }

        return $this->status ? (int) $this->status : null;
    }

    /**
     * Disable Page Cache
     *
     * @return $this
     */
    public function disablePageCache()
    {
        nocache_headers();

        return $this;
    }

    /**
     * Get message
     *
     * Get the message used in the flash alert on front-end
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get Data
     *
     * @param null|string $key
     *
     * @return array|string|int
     */
    public function getData( $key = null )
    {
        if( array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $this->data;
    }

    /**
     * Get Redirect
     *
     * Get redirect URL used by the AJAX REST API
     *
     * @return bool
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Get Flash
     *
     * Get the flash property to see if the front-end should
     * flash the message.
     *
     * @return bool
     */
    public function getFlash()
    {
        return $this->flash;
    }

    /**
     * Block Flash
     *
     * Block the flashing no matter what.
     *
     * @return $this
     */
    public function blockFlash()
    {
        $this->blockFlash = true;

        return $this;
    }

    /**
     * Allow Flash
     *
     * Disable block flash
     *
     * @return $this
     */
    public function allowFlash()
    {
        $this->blockFlash = false;

        return $this;
    }

    /**
     * Lock Flash
     *
     * @return $this
     */
    public function lockFlash()
    {
        $this->lockFlash = true;

        return $this;
    }

    /**
     * Unlock Flash
     *
     * @return $this
     */
    public function unlockFlash()
    {
        $this->lockFlash = false;

        return $this;
    }

    /**
     * Is Flash Locked
     *
     * @return bool
     */
    public function flashLocked()
    {
        return $this->lockFlash;
    }

    /**
     * Get Response Properties
     *
     * Return the private properties that make up the response
     *
     * @param bool $withReturn
     * @return array
     */
    public function toArray($withReturn = false)
    {
        $vars = get_object_vars($this);

        if(!$withReturn) {
            unset($vars['return']);
        }

        return $vars;
    }

    /**
     * With with data
     *
     * @param array $data
     *
     * @return Response $this
     */
    public function withRedirectData($data = null)
    {
        $data = $data ?? $this->data;

        if( !empty( $data ) ) {
            (new Cookie)->setTransient(Redirect::KEY_DATA, $data);
        }

        return $this;
    }

    /**
     * With with data
     *
     * @param array|null $errors
     * @param bool $merge
     *
     * @return Response $this
     */
    public function withRedirectErrors($errors = null, $merge = false)
    {
        if($merge) {
            $errors = array_merge($this->errors, $errors ?? []);
        } else {
            $errors = $errors ?? $this->errors;
        }

        if( !empty( $errors ) ) {
            (new Cookie)->setTransient(ErrorCollection::KEY, $errors);
        }

        return $this;
    }

    /**
     * With Message
     *
     * @param string|null $message
     * @param string|null $type options: success, error, warning, and info
     *
     * @return $this
     */
    public function withRedirectMessage($message = null, $type = null)
    {
        $message = $message ?? $this->message;

        if(!empty($message)) {
            (new Cookie)->setTransient(Redirect::KEY_MESSAGE, [
                'message' => $message,
                'type' => $type ?? $this->getMessageType()
            ]);
        }

        return $this;
    }

    /**
     * Response with old fields
     *
     * @param array|Fields|null $fields
     * @param array $notFields
     *
     * @return Response
     */
    public function withOldFields($fields = null, $notFields = []) {

        $fields = $fields ?? (new Request)->getFields();

        if($fields instanceof Fields) {
            $fields = $fields->getArrayCopy();
        }

        if( !empty($fields) ) {
            $send = array_diff_key($fields, array_flip($notFields));
            (new Cookie)->setTransient(Redirect::KEY_OLD, $send);
        }

        return $this;
    }

    /**
     * Flash message on next request
     *
     * When the request is marked as _tr_ajax_request transient is not
     * set by default.
     *
     * @param string $message
     * @param string $type options: success, error, warning, and info
     * @param bool $force_transient
     *
     * @return \TypeRocket\Http\Response $this
     */
    public function flashNext($message, $type = 'success', $force_transient = false)
    {
        if( ! $this->blockFlash && ! headers_sent() && ! ($this->flash && $this->lockFlash) ) {
            $this->flash       = true;
            $this->setMessage($message, $type);

            $data = [
                'type' => $this->getMessageType(),
                'message' => $this->getMessage(),
            ];

            if($force_transient || !(new Request)->isMarkedAjax()) {
                (new Cookie)->setTransient(Redirect::KEY_ADMIN, $data);
            }
        }

        return $this;
    }

    /**
     * Flash message now
     *
     * Display flash message in the WP admin using admin_notices
     *
     * @param string $message
     * @param string $type options: success, error, warning, and info
     * @param string $hook action hook to be used default is admin_notices
     *
     * @return $this
     */
    public function flashNow($message, $type, $hook = 'admin_notices')
    {
        if( ! $this->blockFlash && ! ($this->flash && $this->lockFlash) ) {
            $this->flash       = true;
            $this->setMessage($message, $type);

            $data = [
                'type' => $this->messageType,
                'message' => $this->message,
            ];

            add_action( $hook, \Closure::bind(function() use ($data) {
                Notice::dismissible($data);
            }, $this));
        }

        return $this;
    }

    /**
     * Abort Request
     *
     * Returns a HTML template or JSON response depending
     * on the context of the request.
     *
     * @param null|int $code
     */
    public function abort($code = null)
    {
        \TypeRocket\Exceptions\HttpError::abort($code ?? $this->getStatus());
    }

    /**
     * Exit
     *
     * @param int|null $code
     */
    public function exitAny( $code = null )
    {
        $code = $code ?? $this->getStatus();
        $request = (new Request);

        if( $request->isMarkedAjax() || $request->wants('json') ) {
            $this->exitJson($code);
        }

        $this->exitMessage($code);
    }

    /**
     * Exit with JSON dump
     *
     * @param int|null $code
     */
    public function exitJson( $code = null )
    {
        $code = $code ?? $this->getStatus();
        $this->setStatus($code);
        wp_send_json( $this );
    }

    /**
     * Exit with message
     *
     * @param int|null $code
     */
    public function exitMessage( $code = null )
    {
        $code = $code ?? $this->getStatus();
        $this->setStatus($code);
        wp_die($this->getMessage(), '', $code);
    }

    /**
     * Exit with 404 Page
     */
    public function exitNotFound()
    {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 );
        exit();
    }

    /**
     * Exit as Server Error
     *
     * @param null|string $message
     * @param int $code
     */
    public function exitServerError($message = null, $code = 500)
    {
        $code = $code >= 500 ?: 500;
        status_header( $code );
        wp_die(WP_DEBUG ? ($message ?? $this->getMessage()) : __('Something went wrong!', 'typerocket-domain'), $code );
    }

    /**
     * Finish Response
     *
     * If a request is marked as _tr_ajax_request return special
     * response by default.
     *
     * @param bool $forceResponseArray send special response array
     *
     * @return bool
     */
    public function finish($forceResponseArray = false)
    {
        $response = static::getFromContainer();
        do_action('typerocket_response_finish', $response, $forceResponseArray);

        $statusCode = $response->getStatus();

        if($response->getCancel()) {
            return false;
        }

        if(is_null($statusCode) && $response->hasErrors() ) {
            $statusCode = 500;
        }

        if($statusCode) {
            status_header( $statusCode );
        }

        $returned = $this->getReturn();



        if( $forceResponseArray && $returned instanceof Redirect && $response->getRedirect()) {
            $response->setRedirect($returned)->exitJson();
        }

        if( $forceResponseArray && $returned instanceof Response ) {
            $returned->exitJson($statusCode);
        }

        if( $forceResponseArray ) {
            $response->exitJson($statusCode);
        }

        if(class_exists('\TypeRocketPro\Http\Download')) {
            if( $returned instanceof \TypeRocketPro\Http\Download) {
                $returned->send();
            }
        }

        if( $returned instanceof Redirect) {
            $returned->now();
        }

        if( $returned instanceof Response ) {
            wp_send_json( $returned->toArray() );
        }

        if( $returned instanceof View ) {
            $returned->render();
            die();
        }

        if( $returned instanceof Model ) {
            wp_send_json( $returned->toArray() );
        }

        if( $returned instanceof Results ) {
            wp_send_json( $returned->toArray() );
        }

        if( is_callable($returned) ) {
            call_user_func($returned, $this);
            die();
        }

        if( is_array($returned) || is_object($returned) ) {

            if($returned instanceof \WP_Error) {
                $statusCode = $returned->get_error_code();
                $statusCode = is_numeric($statusCode) ? (int) $statusCode : 500;
            }

            wp_send_json($returned, $statusCode);
        }

        if( is_string($returned) || empty($returned) ) {

            if( Data::isJson($returned) ) {
                $response->send('json');
            }

            echo $returned;
            die();
        }

        return true;
    }

    /**
     * Create Nonce
     *
     * @param string|null $action
     *
     * @return false|string
     */
    public function createNonce($action = null)
    {
        return wp_create_nonce( 'form_' . $action . Config::get('app.seed' ) );
    }

    /**
     * To Json
     *
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this);
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return \TypeRocket\Core\Container::findOrNewSingleton(static::class, static::ALIAS);
    }
}
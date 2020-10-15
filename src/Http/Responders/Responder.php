<?php
namespace TypeRocket\Http\Responders;

use TypeRocket\Exceptions\RedirectError;
use TypeRocket\Http\Handler;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Template\ErrorTemplate;

abstract class Responder
{
    /** @var \TypeRocket\Http\HttpKernel */
    protected $kernel;
    /** @var Handler */
    protected $handler;

    /**
     * Responder constructor.
     * @param Handler|null $handler
     */
    public function __construct(Handler $handler = null)
    {
        $this->handler = $handler ?? new Handler;
    }

    /**
     * Respond
     *
     * @param array $args
     * @return mixed
     */
    abstract function respond( $args );

    /**
     * Run the Kernel
     *
     * @param Request $request
     * @param Response $response
     * @param Handler $handler
     */
    public function runKernel(Request $request, Response $response, Handler $handler )
    {
        try {
            $Kernel = \TypeRocket\Utility\Helper::appNamespace("Http\\Kernel");
            $this->kernel = new $Kernel( $request, $response, $handler);
            $this->kernel->run();
        } catch (\Throwable $e ) {

            $code = $e->getCode();

            if($e instanceof RedirectError) {
                $errorRedirect = $e->redirect();
                $response->setStatus(302);
                $response->setReturn($errorRedirect)->finish();
            }

            if(!$e instanceof \Requests_Exception_HTTP) {
                $code = 500;
                \TypeRocket\Utility\Helper::reportError($e);
            }

            $response->setStatus($code);

            if($request->isMarkedAjax() || $request->wants('json')) {
                $response->exitJson();
            }

            if(is_admin()) {
                $response->exitMessage();
            }

            new ErrorTemplate($code, $handler->getTemplate());
        }
    }

    /**
     * Get Handler
     *
     * @return Handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get Kernel
     *
     * @return \TypeRocket\Http\HttpKernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }
}
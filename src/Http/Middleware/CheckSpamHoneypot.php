<?php
namespace TypeRocket\Http\Middleware
{
    class CheckSpamHoneypot extends Middleware
    {
        public function handle()
        {
            if( ! $this->request->isGet() ) {
                $honey = $this->request->checkHoneypot();
                if ( !$honey ) {
                    add_action('typerocket_honeypot_touched', 'typerocket_honeypot_touched', 20, 2);
                    $this->response->setError('honeypot', true);
                    $this->response->setMessage('A tasty treat.', 'error');
                    do_action('typerocket_honeypot_touched', $this->response);
                }
            }

            $this->next->handle();
        }
    }
}

namespace
{
    use TypeRocket\Http\Response;

    function typerocket_honeypot_touched(Response $response)
    {
        $response->exitAny(200);
    }
}

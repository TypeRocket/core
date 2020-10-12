<?php
namespace TypeRocket\Services
{
    class MailerService extends Service
    {
        protected $driver;
        const ALIAS = 'mail';

        /**
         * @return $this|Service
         */
        public function register() : Service
        {
            $default = tr_config('mail.default');

            if($default) {
                throw new \Exception('mail.php config is missing.');
            }

            $driver = tr_config("mail.mailers.{$default}.driver");

            $this->driver = new $driver;

            return $this;
        }

        /**
         * @param \TypeRocketPro\Utility\Mailers\MailDriver|null $driver
         *
         * @return \TypeRocketPro\Utility\Mailers\MailDriver|null
         */
        public function driver(\TypeRocketPro\Utility\Mailers\MailDriver $driver = null)
        {
            if(func_num_args() == 0) {
                return $this->driver;
            }

            return $this->driver = $driver;
        }

        public function send($to, $subject, $message, $headers = '', $attachments = []) : bool
        {
            return $this->driver()->send(...func_get_args());
        }
    }
}

namespace
{
    use TypeRocket\Services\MailerService;

    if(!function_exists('wp_mail') && tr_config('mail.default')) {
        function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] )
        {
            /**
             * @var MailerService $mailer
             */
            $mailer = tr_container(MailerService::ALIAS);

            return $mailer->send(...func_get_args());
        }
    }
}
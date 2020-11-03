<?php
namespace TypeRocket\Services
{
    use TypeRocket\Core\Config;
    use TypeRocket\Core\Container;

    class MailerService extends Service
    {
        protected $driver;
        public const ALIAS = 'mail';

        /**
         * @return $this|Service
         * @throws \Exception
         */
        public function register() : Service
        {
            $default = Config::get('mail.default');

            if(!$default) {
                throw new \Exception('mail.php config is missing.');
            }

            $driver = Config::get("mail.drivers.{$default}.driver");

            $this->driver( apply_filters('typerocket_mailer_service_driver', new $driver ) );

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

        /**
         * @return static
         */
        public static function getFromContainer()
        {
            return Container::resolve(static::ALIAS);
        }
    }
}

namespace
{
    use TypeRocket\Services\MailerService;

    if(!function_exists('wp_mail') && \TypeRocket\Core\Config::get('mail.default')) {
        function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] )
        {
            /**
             * @var MailerService $mailer
             */
            $mailer = MailerService::getFromContainer();

            return $mailer->send(...func_get_args());
        }
    }
}
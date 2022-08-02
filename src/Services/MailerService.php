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

        /**
         * @param string|string[] $to Array or comma-separated list of email addresses to send message.
         * @param string $subject Email subject.
         * @param string $message Message contents.
         * @param string|string[] $headers Additional headers.
         * @param string|string[] $attachments Paths to files to attach.
         * @return bool
         */
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

    function typerocket_mail_service_override_wp_mail($return, $args): bool
    {
        $mailer = MailerService::getFromContainer();

        return $mailer->send($args['to'], $args['subject'], $args['message'], $args['headers'], $args['attachments']);
    }

    if(\TypeRocket\Core\Config::get('mail.default')) {
        add_filter('pre_wp_mail', 'typerocket_mail_service_override_wp_mail', 0, 2);
    }
}
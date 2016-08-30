<?php
class UserTest extends PHPUnit_Framework_TestCase
{
    static public $sharedUserId = 2;

    public function testCreateWithSlashing()
    {
        $user = new \TypeRocket\Models\WPUser();

        $data = [
            'user_nicename' => 'new\nice\name',
            'user_login' => 'new\nice\name',
            'user_url' => 'http://typerocket.com/ok\\that',
            'display_name' => 'new \\is \'me\' \ok',
        ];

        $user->create($data);

        self::$sharedUserId = $user->getID();

        $nicename = $user->getProperty('user_nicename');
        $login = $user->getProperty('user_login');
        $url = $user->getProperty('user_url');
        $display = $user->getProperty('display_name');

        // Users user_nicename and user_login can not have slashes
        $this->assertTrue($nicename == wp_unslash($data['user_nicename']));
        $this->assertTrue($login == wp_unslash($data['user_login']));
        $this->assertTrue($url == wp_unslash($data['user_url']));
        $this->assertTrue($display == $data['display_name']);
    }

    public function testUpdateWithSlashing()
    {
        $user = new \TypeRocket\Models\WPUser();
        $user->findById( self::$sharedUserId );

        $data = [
            'user_nicename' => 'updated\nice\name',
            'user_url' => 'http://typerocket.com/ok\\that',
            'display_name' => 'updated \\is \'me\' \ok',
        ];

        $user->update($data);

        wp_delete_user( self::$sharedUserId );

        $nicename = $user->getProperty('user_nicename');
        $url = $user->getProperty('user_url');
        $display = $user->getProperty('display_name');

        // Users user_nicename and user_login can not have slashes
        $this->assertTrue($nicename == wp_unslash($data['user_nicename']));
        $this->assertTrue($url == wp_unslash($data['user_url']));
        $this->assertTrue($display == $data['display_name']);
    }
}
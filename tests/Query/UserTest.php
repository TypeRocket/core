<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Exceptions\ModelException;
use TypeRocket\Exceptions\ModelNotFoundException;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

class UserTest extends TestCase
{
    static public $sharedUserId = 2;

    public function testUserDisabledCache()
    {
        $user = WPUser::new()->whereMeta('nickname', '!=', '')->select('user_email')->disableCache()->get()->toArray();

        $this->assertTrue( !!filter_var($user[0]['user_email'], FILTER_VALIDATE_EMAIL) );
    }

    public function testUserIsAdministrator()
    {
        $user = WPUser::new()->whereCapabilityLike('administrator')->select('user_email')->disableCache()->get();

        $this->assertTrue( !!filter_var($user->toArray()[0]['user_email'], FILTER_VALIDATE_EMAIL) );
    }

    public function testUserEnabledCache()
    {
        // This uses the Obj cache and will break WP as all the fields
        // are required for the object cache to work properly.
        // You should always disabled the object cache.
        $user = WPUser::new()->whereMeta('nickname', '!=', '')->select('user_email', 'ID', 'user_login', 'user_nicename')->get()->toArray();

        $this->assertTrue( !!filter_var($user[0]['user_email'], FILTER_VALIDATE_EMAIL) );
    }

    public function testCreateWithSlashing()
    {
        $user = new WPUser();

        $data = [
            'user_nicename' => 'new\nice\name',
            'user_login' => 'new\nice\name',
            'user_pass' => 'password',
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
        $user = new WPUser();
        $user->findById( self::$sharedUserId );

        $data = [
            'user_nicename' => 'updated\nice\name',
            'user_url' => 'http://typerocket.com/ok\\that',
            'display_name' => 'updated \\is \'me\' \ok',
        ];

        $user->update($data);

        $nicename = $user->getProperty('user_nicename');
        $url = $user->getProperty('user_url');
        $display = $user->getProperty('display_name');

        // Users user_nicename and user_login can not have slashes
        $this->assertTrue($nicename == wp_unslash($data['user_nicename']));
        $this->assertTrue($url == wp_unslash($data['user_url']));
        $this->assertTrue($display == $data['display_name']);
    }

    public function testUpdateWithSlashingSameUserLogin()
    {
        $user = new WPUser();
        $user->findById( self::$sharedUserId );

        $data = [
            'user_login' => 'newnicename',
        ];

        try {
            $user->update($data);
            $updated = true;
        } catch (ModelException $e) {
            $updated = false;
        }

        $user_login = $user->getProperty('user_login');

        $this->assertTrue($updated);
        $this->assertTrue($user_login === $data['user_login']);
    }

    public function testUpdateWithSlashingNewUserLogin()
    {
        $user = new WPUser();
        $user->findById( self::$sharedUserId );

        $data = [
            'user_login' => 'updated_user_login',
        ];

        try {
            $user->update($data);
            $updated = true;
        } catch (ModelException $e) {
            $updated = false;
        }

        $user_login = $user->getProperty('user_login');

        $this->assertFalse($updated);
        $this->assertTrue($user_login !== $data['user_login']);
    }

    public function testUsersPosts()
    {
        $user = new WPUser();
        $post = $user->findById(1)->posts( WPPost::class );
        $results = $post->get();

        foreach ($results as $result ) {
            $this->assertTrue( $result instanceof WPPost);
        }

        $this->assertTrue( $post instanceof WPPost);
    }

    public function testUserPrivateProperties()
    {
        $user = new WPUser();
        $result = $user->findById( 1 )->toArray();

        $this->assertTrue( !isset($result['user_pass']));
        $this->assertTrue( isset($result['user_login']));
    }

    public function testUserNotFound()
    {
        $user = new WPUser();
        $result = $user->findById( 0 );

        $this->assertTrue( $result === null);
    }

    public function testUserDataCleanup()
    {
        $this->assertTrue(wp_delete_user( self::$sharedUserId ));
    }
}
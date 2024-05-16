<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Database\Results;
use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

/**
 * @property int $product_number
 * @property string $title
 * @property VariantTestModel[]|Results $variants
 */
class ProductTestModel extends Model
{
    protected $table = 'products';
    protected $idColumn = 'product_number';
    protected $fillable = ['product_number', 'title'];

    public function variants()
    {
        return $this->belongsToMany(VariantTestModel::class, 'products_variants', 'product_number', 'variant_sku', null, true, 'product_number');
    }
}

/**
 * @property string $sku
 * @property string $barcode
 * @property ProductTestModel[]|Results $products
 */
class VariantTestModel extends Model
{
    protected $table = 'variants';
    protected $idColumn = 'sku';
    protected $fillable = ['sku', 'barcode'];

    public function products()
    {
        return $this->belongsToMany(ProductTestModel::class, 'products_variants', 'variant_sku', 'product_number', null, true, 'sku');
    }
}

/**
 * @property int $id
 * @property string $sku
 * @property int $product_number
 */
class ProductVariantTestModel extends Model
{
    protected $table = 'products_variants';
}

/**
 * @property int $id
 * @property string $sku
 * @property int $product_number
 */
class OrderTestModel extends Model
{
    protected $table = 'orders';
    protected $fillable = ['name', 'per_number'];

    public function person()
    {
        return $this->belongsTo(PeopleTestModel::class, 'per_number', 'p_number');
    }

    public function item()
    {
        return $this->belongsTo(ItemTestModel::class, 'id', 'order_id');
    }
}

/**
 * @property int $id
 * @property string $sku
 * @property int $product_number
 */
class ItemTestModel extends Model
{
    protected $table = 'items';
    protected $fillable = ['name', 'order_id'];

    public function order()
    {
        return $this->hasOne(OrderTestModel::class, 'id', 'order_id');
    }
}

/**
 * @property int $id
 * @property string $p_number
 * @property string $name
 * @property RolesTestModel[]|Results $roles
 */
class PeopleTestModel extends Model
{
    protected $table = 'peoples';
    protected $fillable = ['name', 'p_number'];

    public function roles()
    {
        return $this->belongsToMany(RolesTestModel::class, 'peoples_roles', 'people_number', 'role_number', null, true, 'p_number', 'r_number');
    }

    public function rolesNameIsAdmin()
    {
        return $this->roles()->where('roles.name', 'admin');
    }

    public function rolesNameIsSubscriber()
    {
        return $this->roles()->where('roles.name', 'subscriber');
    }

    public function orders()
    {
        return $this->hasMany(OrderTestModel::class, 'per_number', 'p_number');
    }
}

/**
 * @property int $id
 * @property string $r_number
 * @property string $name
 * @property PeopleTestModel[]|Results $peoples
 */
class RolesTestModel extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'r_number'];

    public function peoples()
    {
        return $this->belongsToMany(PeopleTestModel::class, 'peoples_roles', 'role_number', 'people_number', null, true, 'r_number', 'p_number');
    }

    public function peoplesNameIsKevin()
    {
        return $this->peoples()->where('peoples.name', 'kevin');
    }
}

/**
 * @property int $id
 * @property int $people_number
 * @property int $role_number
 */
class PeoplesRolesTestModel extends Model
{
    protected $table = 'peoples_roles';
}

class RelationshipTest extends TestCase
{
    public function testBelongsTo()
    {
        $meta = new WPPostMeta();
        $post = $meta->findById(1)->post();
        $sql = $post->getSuspectSQL();
        $expected = "SELECT * FROM `wp_posts` WHERE `wp_posts`.`ID` = '2' LIMIT 1 OFFSET 0";
        $rel = $post->getRelatedModel();
        $this->assertTrue( $rel instanceof WPPostMeta );
        $this->assertTrue($sql == $expected);
    }

    public function testBelongsEagerLoad()
    {
        $post = new WPPost();

        $numRun = Query::$numberQueriesRun;

        $result = $post->with(['author.meta', 'meta.post'])->findAll([1,2,3])->get();

        foreach ($result as $item) {
            $this->assertTrue( $item->author instanceof WPUser );
            $this->assertTrue( $item->getRelationship('author') instanceof WPUser );
            $this->assertTrue( $item->author->meta instanceof ResultsMeta);
            $this->assertTrue( $item->meta instanceof ResultsMeta);

            foreach ($item->meta as $meta) {
                $this->assertTrue( $meta->post instanceof WPPost);
            }
        }

        $numRun = Query::$numberQueriesRun - $numRun;

        $this->assertTrue( $numRun === 5 );
    }

    public function testProductsVariantsTest()
    {
        static $i = 123;
        /** @var VariantTestModel $variant */
        $variant = VariantTestModel::new()->saveAndGet(['sku' => $i, 'barcode' => 'ABC']);

        /** @var ProductTestModel $product */
        $product = ProductTestModel::new()->saveAndGet(['product_number' => ++$i, 'title' => 'product ' . $i]);

        $product->variants()->attach([$variant?->sku]);

        /** @var VariantTestModel[] $pv */
        $pv = $product->variants()->get();

        $this->assertTrue($pv[0] instanceof VariantTestModel);
        $this->assertTrue($pv[0]->barcode === 'ABC');

        /** @var VariantTestModel $variant */
        $variant = $variant->load('products');
        $productLoaded = $variant->products[0];

        $this->assertTrue($productLoaded instanceof ProductTestModel);
        $this->assertTrue(!$productLoaded->sku);
        $this->assertTrue($productLoaded->product_number === (string) $i);
        $this->assertTrue($productLoaded->title === 'product ' . $i);
        $this->assertTrue(!$productLoaded->the_relationship_id);
    }

    public function testPeoplesRolesTest()
    {
        global $wpdb;

        $person2 = PeopleTestModel::new()->saveAndGet(['p_number' => 100, 'name' => 'kim']);
        RolesTestModel::new()->saveAndGet(['r_number' => 200, 'name' => 'subscriber']);

        /** @var RolesTestModel $role */
        $role = RolesTestModel::new()->saveAndGet(['r_number' => 123, 'name' => 'admin']);
        $role2 = RolesTestModel::new()->saveAndGet(['r_number' => 100, 'name' => 'reader']);

        /** @var PeopleTestModel $person */
        $person = PeopleTestModel::new()->saveAndGet(['p_number' => 987, 'name' => 'kevin']);
        $order1 = OrderTestModel::new()->saveAndGet(['per_number' => $person->p_number, 'name' => 'AA']);

        $this->assertTrue($order1->has('item')->get() === null);

        $order2 = OrderTestModel::new()->saveAndGet(['per_number' => $person->p_number, 'name' => 'ZZ']);

        ItemTestModel::new()->save(['order_id' => $order1->getID(), 'name' => 'item1']);
        ItemTestModel::new()->save(['order_id' => $order2->getID(), 'name' => 'item2']);

        $hasItems = OrderTestModel::new()->has('item')->get();
        $this->assertTrue($hasItems instanceof Results);
        $this->assertTrue($hasItems->count() === 2);

        $order1->load(['item']);
        $order2->load(['item']);
        $this->assertTrue($order1->item instanceof ItemTestModel);
        $this->assertTrue($order2->item instanceof ItemTestModel);

        $person->roles()->attach([$role->r_number, $role2]);

        /** @var RolesTestModel[]|Results $roles */
        $roles = $person->roles()->get();
        $orders = $person->orders()->get();

        $this->assertTrue($roles[0] instanceof RolesTestModel);
        $this->assertTrue($roles->count() === 2);
        $this->assertTrue($orders->count() === 2);
        $this->assertTrue($roles[0]->name === 'admin');
        $this->assertTrue($roles[0]->r_number === '123');

        /** @var PeopleTestModel $person */
        $person = PeopleTestModel::new()->find(1)->load('roles');
        $this->assertTrue($person->roles === null);

        /** @var PeopleTestModel $person */
        $person = PeopleTestModel::new()->find(2)->load('roles');
        $this->assertTrue($person->roles->count() === 2);

        /** @var RolesTestModel $role */
        $role = $role->load('peoples');
        $peoplesLoaded = $role->peoples[0];

        $this->assertTrue($peoplesLoaded instanceof PeopleTestModel);
        $this->assertTrue($role->peoples->count() === 1);
        $this->assertTrue($peoplesLoaded->p_number === '987');
        $this->assertTrue($peoplesLoaded->name === 'kevin');
        $this->assertTrue(!$peoplesLoaded->r_number);
        $this->assertTrue(!$peoplesLoaded->the_relationship_id);

        /** @var PeopleTestModel[]|Results $variants **/
        $persons = PeopleTestModel::new()->has('roles')->get();
        $this->assertTrue($persons->count() === 1);
        $this->assertTrue($persons->first()->name === 'kevin');

        $person2->roles()->attach([$role2]);

        /** @var PeopleTestModel[]|Results $variants **/
        $persons = PeopleTestModel::new()->has('roles')->get();
        $this->assertTrue($persons->count() === 2);

        /** @var PeopleTestModel[]|Results $peoples */
        $peoples = RolesTestModel::new()->find(2)->peoplesNameIsKevin()->has('rolesNameIsAdmin')->get();
        $this->assertTrue($peoples->count() === 1);

        /** @var PeopleTestModel[]|Results $peoples */
        $peoples = RolesTestModel::new()->find(2)->peoplesNameIsKevin()->has('rolesNameIsSubscriber')->get();
        $this->assertTrue($peoples === null);

        /** @var RolesTestModel[]|Results $roles */
        $roles = RolesTestModel::new()->has('peoples')->get();
        $this->assertTrue($roles->count() === 2);

        /** @var RolesTestModel[]|Results $roles */
        $roles = RolesTestModel::new()->hasNo('peoples')->get();
        $this->assertTrue($roles->count() === 1);

        $persons = PeopleTestModel::new()->has('orders')->get();
        $this->assertTrue($persons->count() === 1);
        $this->assertTrue($persons->first()->name === 'kevin');

        $persons = PeopleTestModel::new()->hasNo('orders')->get();
        $this->assertTrue($persons->count() === 1);
        $this->assertTrue($persons[0]->name === 'kim');
    }

    public function testRelationshipExistsScopedNested()
    {
        global $wpdb;

        $persons = PeopleTestModel::new()->has('orders', function(Model $query) {
            $query->has('item', function(Model $query) {
                $table = $query->getTable();
                $query->where("{$table}.name", 'item1');
            });
        })->get();

        $this->assertTrue($persons->count() === 1);
        $this->assertTrue($persons[0]->name === 'kevin');
    }

    public function testRelationshipExistsOrSomethingElse()
    {
        global $wpdb;

        $persons = PeopleTestModel::new()->where('peoples.name', 'kevin')->hasNo('orders', null, 'OR')->get();

        $this->assertTrue($persons->count() === 2);
        $this->assertTrue($persons[0]->name === 'kim');
        $this->assertTrue($persons[1]->name === 'kevin');
    }
}
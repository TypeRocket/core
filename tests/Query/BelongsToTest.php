<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Query;
use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Meta\WPPostMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPUser;

/**
 * @property int $product_number
 * @property string $title
 */
class ProductTest extends Model
{
    protected $table = 'products';
    protected $idColumn = 'product_number';
    protected $fillable = ['product_number', 'title'];

    public function variants()
    {
        return $this->belongsToMany(VariantTest::class, 'products_variants', 'product_number', 'variant_sku', null, true, 'product_number');
    }
}

/**
 * @property string $sku
 * @property string $barcode
 */
class VariantTest extends Model
{
    protected $table = 'variants';
    protected $idColumn = 'sku';
    protected $fillable = ['sku', 'barcode'];

    public function products()
    {
        return $this->belongsToMany(ProductTest::class, 'products_variants', 'variant_sku', 'product_number', null, true, 'sku');
    }
}

/**
 * @property int $id
 * @property string $sku
 * @property int $product_number
 */
class ProductVariantTest extends Model
{
    protected $table = 'products_variants';
}

class BelongsToTest extends TestCase
{
    public function testBelongsTo()
    {
        $meta = new WPPostMeta();
        $post = $meta->findById(1)->post();
        $sql = $post->getSuspectSQL();
        $expected = "SELECT * FROM `wp_posts` WHERE `ID` = '2' LIMIT 1 OFFSET 0";
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

    public function testProductTest()
    {
        /** @var VariantTest $variant */
        $variant = VariantTest::new()->saveAndGet(['sku' => 'ABC', 'barcode' => '987']);

        /** @var ProductTest $product */
        $product = ProductTest::new()->saveAndGet(['product_number' => 123, 'title' => 'product 1']);

        $product->variants()->attach([$variant->sku]);

        $pv = $product->variants()->get();

        $this->assertTrue($pv[0] instanceof VariantTest);
        $this->assertTrue($pv[0]->barcode === '987');

        $variant = $variant->load('products');
        $productLoaded = $variant->products[0];

        $this->assertTrue($productLoaded instanceof ProductTest);
        $this->assertTrue(!$productLoaded->sku);
        $this->assertTrue($productLoaded->product_number === '123');
        $this->assertTrue($productLoaded->title === 'product 1');
        $this->assertTrue(!$productLoaded->the_relationship_id);
    }
}
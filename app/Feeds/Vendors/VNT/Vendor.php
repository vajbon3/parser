<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Storage\AbstractFeedStorage;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;
use App\Repositories\DxRepositoryInterface;

class Vendor extends HttpProcessor
{

    protected array $first = [ "https://vanatisanes.com/"];

    protected const CHUNK_SIZE = 10;
    protected const DELAY_S = 3;
    protected const REQUEST_TIMEOUT_S = 60;
    protected const STATIC_USER_AGENT = true;

    // фид с индексом имён для O(1) поиска
    public array $feed = [];

    protected array $headers = [
        "Accept" => "*/*",
        "Host" => "vanatisanes.com"
    ];

    public const CATEGORY_LINK_CSS_SELECTORS = [".menu-item > a"];
    public const PRODUCT_LINK_CSS_SELECTORS = [".product-category.product-info"];

    public function filterProductLinks(Link $link): bool
    {
        return str_contains($link->getUrl(),"product");
    }

    public function isValidFeedItem(FeedItem $fi): bool
    {
        return $fi->getProduct() !== null;
    }

    // индексируем каждый новый FeedItem в мап feed для O(1) поиска
    public function afterProcessItem()
    {
        $last_key = key(array_slice($this->feed_items, -1, 1, true));
        if($last_key != "") {
            $item = $this->feed_items[$last_key];
            $this->feed[$item->getProduct()] = &$item;
        }
    }

    // вставим дочерные продукты которые ешё не вставлени
    public function afterProcess()
    {
        foreach($this->feed_items as $feed_item) {
            $i = 0;
            foreach($feed_item->child_products as &$child_product) {
                if(isset($child_product->alt_names[0])) {
                    $child_product = clone $this->feed[$child_product->alt_names[0]];
                    $child_product->setMpn($feed_item->getProduct()."-".$i);
                    $child_product->setProductCode($feed_item->getProductCode()."-".$i);
                    $i++;

                    // ставим скидочную цену
                    $this->setBundleProductPrice($child_product);
                }
            }
        }
    }

    // хелпер методы
    public function setBundleProductPrice(&$fi) {
        foreach($fi->child_products as &$childProduct) {
            $original_price = $childProduct->getListPrice() ?? 0.0;
            $childProduct->setCostToUs($original_price - (15/100 * $original_price));
            echo $childProduct->product." discount price: ".$childProduct->cost_to_us.PHP_EOL;
        }
    }
}
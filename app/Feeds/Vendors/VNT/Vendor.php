<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Storage\AbstractFeedStorage;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;
use App\Repositories\DxRepositoryInterface;

class Vendor extends SitemapHttpProcessor
{

    protected array $first = [ "https://vanatisanes.com/product-sitemap.xml"];

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

    //public const CATEGORY_LINK_CSS_SELECTORS = [".menu-item > a"];
    //public const PRODUCT_LINK_CSS_SELECTORS = [".product-category.product-info"];

    public function filterProductLinks(Link $link): bool
    {
        return str_contains($link->getUrl(),"product");
    }

    public function isValidFeedItem(FeedItem $fi): bool
    {
        return !in_array("Bundles", $fi->getCategories(), true) && !in_array("Gifts", $fi->getCategories(), true);
    }

    // хелпер методы
    public function isBundle(FeedItem $fi): bool {
        return in_array("Bundles", $fi->getCategories(), true);
    }
}
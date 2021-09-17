<?php


namespace App\Feeds\Vendors\TEST;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Storage\AbstractFeedStorage;
use App\Feeds\Utils\Link;
use App\Repositories\DxRepositoryInterface;

class Vendor extends HttpProcessor
{
    public const DELAY_S = 1;
    public const REQUEST_TIMEOUT_S = 60;
    public const CHUNK_SIZE = 10;

    #public const CATEGORY_LINK_CSS_SELECTORS = [ ".ty-menu__items > li > a"];
    public const PRODUCT_LINK_CSS_SELECTORS = [ "#pagination_contents div > form > div > .product-title"];
    #,".ty-pagination__bottom .ty-pagination .ty-pagination__item"

    protected array $first = [ 'https://adashop.ge/%E1%83%99%E1%83%9D%E1%83%9B%E1%83%9E%E1%83%98%E1%83%A3%E1%83%A2%E1%83%94%E1%83%A0%E1%83%98-%E1%83%9C%E1%83%9D%E1%83%A3%E1%83%97%E1%83%91%E1%83%A3%E1%83%A5%E1%83%98/' ];

    public function filterProductLinks(Link $link): bool
    {
        return true;
    }

    protected function isValidFeedItem(FeedItem $fi): bool
    {
        return true;
    }

}
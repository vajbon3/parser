<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;

class Vendor extends HttpProcessor
{
    protected array $first = [ "https://vanatisanes.com/"];

    public const CATEGORY_LINK_CSS_SELECTORS = [".menu-item > a"];
    public const PRODUCT_LINK_CSS_SELECTORS = [".product_type_variable"];

    public function filterProductLinks(Link $link): bool
    {
        return str_contains($link->getUrl(),"product");
    }

    public function isValidFeedItem(FeedItem $fi): bool
    {
        return $fi->getMpn() !== "";
    }
}
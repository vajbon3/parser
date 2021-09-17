<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;

class Parser extends \App\Feeds\Parser\HtmlParser
{

    public function getProduct(): string
    {
        echo $this->getText('.product_title');
        return $this->getText('.product_title') ?? "default name";
    }


    public function getCostToUs(): float
    {
        // todo
        return 0.5;
    }

    public function getMpn(): string
    {
        return $this->getText(".sku") ?? "";
    }

    public function getImages(): array
    {
        return [$this->getAttr(".wp-post-image.lazyloaded","src")];
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getListPrice(): ?float
    {
        // todo
        return 0.5;
    }

    public function getDescription(): string
    {
        return $this->getHtml(".woocommerce-product-details__short-description");
    }

    public function getShortDescription(): array
    {
        // todo
        return ["feature1"];
    }

    public function isGroup(): bool
    {
        // todo
        return false;
    }

    public function getChildProducts(FeedItem $parent_fi): array
    {
        // todo
        return [];
    }
}
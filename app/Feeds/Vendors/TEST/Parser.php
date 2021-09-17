<?php


namespace App\Feeds\Vendors\TEST;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{

    public function getProduct(): string
    {
        echo $this->getText('.ty-product-block-title');
        return $this->getText('.ty-product-block-title') ?? "default name";
    }


    public function getCostToUs(): float
    {
        return $this->getMoney(".ty-price-num") ?? 6.9;
    }

    public function getMpn(): string
    {
        return "default sku";
    }

    public function getImages(): array
    {
        return [$this->getAttr(".ty-pict","src")] ?? [
            "https://vanatisanes.com/wp-content/uploads/2017/08/Mouth-02790-1024x683.jpg"
            ];
    }

    /*  public function getAvail(): ?int
    {
        $in_stock = $this->getText(".ty-qty-in-stock");
        return $in_stock == "მარაგშია" ? self::DEFAULT_AVAIL_NUMBER : 0;
    } */

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getBrand(): ?string
    {
        return $this->getText(".ty-product-feature:nth-child(1) span") ?? "Default brand";
    }

    public function getListPrice(): ?float
    {
        return $this->getMoney(".ty-price-num") ?? 6.9;
    }

    public function getDescription(): string
    {
        return "sample description";
    }

    public function getShortDescription(): array
    {
        return ["feature1"];
    }

    public function isGroup(): bool
    {
        return false;
    }

    public function getChildProducts(FeedItem $parent_fi): array
    {
        return [];
    }
}
<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;
use JetBrains\PhpStorm\Pure;

class Parser extends \App\Feeds\Parser\HtmlParser
{
    public array $images;
    public array $attributes;
    public array $variations;

    // взять json дочерных продуктов с саита
    public function beforeParse(): void
    {
        if($this->getCategories()[0] !== "Bundles") {
            $json = html_entity_decode($this->getAttr(".variations_form","data-product_variations"));

            $this->variations = json_decode($json,true);
        } else $this->variations = [];
    }

    public function getAvail(): ?int {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getProductCode(): string
    {
        return $this->getVendor()->getSupplierName()."-".$this->getProduct();
    }

    public function getProduct(): string
    {
        return $this->getText('.product_title');
    }

    public function getMpn(): string
    {
        if(!$this->isGroup())
            return $this->getText(".sku");
        return "";
    }

    public function getImages(): array
    {
        if(!$this->isGroup())
            return $this->parseSrcset($this->variations[0]["image"]["srcset"]);
        return [];
    }

    public function getDescription(): string
    {
        return $this->getHtml(".woocommerce-product-details__short-description");
    }

    public function getCategories(): array
    {
        $categories = [];
        $this->filter(".posted_in a")->each(function (ParserCrawler $c) use (&$categories) {
            $categories[] = $c->text();
        });

        return $categories;
    }

    public function getCostToUs(): float
    {
        if(!$this->isGroup())
            return 5.5;
        return 0;
    }

    public function getListPrice(): ?float
    {
        if(!$this->isGroup())
            return 6.5;
        return null;
    }

    public function isGroup(): bool
    {
        return true;
    }

    public function getChildProducts(FeedItem $parent_fi): array
    {
        $child = [];
        $i = 0;
        if(!$this->isBundle($parent_fi))
            foreach($this->variations as $variation) {
            $fi = clone $parent_fi;

            $fi->setProductCode($this->getVendor()->getSupplierName() . "-" . $i);
            $fi->setMpn($variation["sku"]."-".$i);
            $fi->setCostToUs(StringHelper::getMoney($variation["display_price"]));
            $fi->setListPrice(StringHelper::getMoney($variation["display_regular_price"]));
            $fi->setRAvail($variation["is_in_stock"] ? self::DEFAULT_AVAIL_NUMBER : 0);
            $fi->setImages($this->parseSrcset($variation["image"]["srcset"]));

            $i++;
            array_push($child,$fi);
        }
        // если bundle
        else {
            $i =0;
            // продукты bundle
            $this->filter(".woosb-product")->each(function (ParserCrawler $c) use (&$child,$parent_fi,&$i) {

                $productName = $c->getAttr(".woosb-product", "data-name");

                // если такой дочерный продукт уже был парсирован, взять из индексированного масива
                // если нет, вставить напоминание
                if (isset($this->getVendor()->feed[$productName])) {
                    $product = clone $this->getVendor()->feed[$productName];
                    $product->setMpn($product->getProduct() . "-" . $i);
                    $product->setProductCode($this->getVendor()->getSupplierName() . "-" . $i);
                }
                else {
                    $product = clone $parent_fi;
                    $product->setAltNames([$productName]);
                }

                // ставим скидучную цену
                $this->getVendor()->setBundleProductPrice($product);


                $i++;
                array_push($child,$product);
            });
        }

        return $child;
    }

    public function getShortDescription(): array
    {
        $arr = [];
        $this->filter(".woocommerce-product-attributes")->each(function(ParserCrawler $c) use (&$arr) {
            $arr[$c->getText(".woocommerce-product-attributes-item__label")] = $c->getText(".woocommerce-product-attributes-item__value");
        });

        return $arr != [] ? $arr : ["empty description"];
    }

    // хелпер методы
    private function isBundle(FeedItem $fi) {
        return $fi->getCategories()[0] === "Bundles";
    }

    private function parseSrcset(string $srcset)
    {
        $arr = explode(",", $srcset);
        foreach($arr as &$str) {
            $str = explode(" ",trim($str))[0];
        }

        return $arr;
    }

    private function parseAttributes(array $variation)
    {
        $attributes = [];
        foreach($variation["attributes"] as $key => $value) {
            $attributes[$key] = $value;
        }

        return $attributes;
    }
}
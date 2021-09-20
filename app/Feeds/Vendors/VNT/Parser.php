<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;
use JetBrains\PhpStorm\Pure;

class Parser extends \App\Feeds\Parser\HtmlParser
{
    public array $variations;

    // взять json дочерных продуктов с саита
    public function beforeParse(): void
    {
        $json = html_entity_decode($this->getAttr(".variations_form", "data-product_variations"));

        $this->variations = json_decode($json, true);
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getProduct(): string
    {
        return $this->getText('.product_title');
    }

    public function getMpn(): string
    {
        if (!$this->isGroup())
            return $this->getText(".sku");
        return "";
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

    public function isGroup(): bool
    {
        return true;
    }

    public function getAttributes(): ?array
    {
        $arr = [];
        $this->filter(".woocommerce-product-attributes-item")->each(function (ParserCrawler $c) use(&$arr) {
           $attr_name = $c->getText(".woocommerce-product-attributes-item__label");
           $attr_value = $c->getText(".woocommerce-product-attributes-item__value");

           $arr[$attr_name] = $attr_value;
        });

        if($arr != [])
            return $arr;
        return null;
    }

    public function getChildProducts(FeedItem $parent_fi): array
    {
        $child = [];
        if($this->getVendor()->isBundle($parent_fi))
            return $child;

        $i = 0;
        foreach ($this->variations as $variation) {
            $fi = clone $parent_fi;

            $label = $this->getText(".label > label") ?? "Size";
            $label_for = $this->getAttr(".label > label","for") ?? "pa_size";
            $attribute_key = "attribute_".$label_for;
            $fi->product = $label.": ".ltrim($variation["attributes"][$attribute_key], '$');
            $fi->setMpn($variation["sku"] . "-" . $variation["variation_id"]);
            $fi->setCostToUs(StringHelper::getMoney($variation["display_price"]));
            $fi->setListPrice(StringHelper::getMoney($variation["display_regular_price"]));
            $fi->setRAvail($variation["is_in_stock"] ? self::DEFAULT_AVAIL_NUMBER : 0);
            $fi->setImages($this->parseSrcset($variation["image"]["srcset"]));
            $fi->setAttributes($this->parseAttributes($variation));
            $i++;
            array_push($child, $fi);

        }

        return $child;
    }

    // хелпер методы
    private function parseSrcset(string $srcset)
    {
        $arr = explode(",", $srcset);
        foreach ($arr as &$str) {
            $str = explode(" ", trim($str))[0];
        }

        return $arr;
    }

    private
    function parseAttributes(array $variation)
    {
        $attributes = [];
        foreach ($variation["attributes"] as $key => $value) {
            $attributes[$key] = ltrim($value, '$');
        }

        return $attributes;
    }
}
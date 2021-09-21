<?php


namespace App\Feeds\Vendors\VNT;


use App\Feeds\Feed\FeedItem;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;
use Dotenv\Util\Str;
use JetBrains\PhpStorm\Pure;

class Parser extends \App\Feeds\Parser\HtmlParser
{
    public array $variations;

    public function parseContent(Data $data, array $params = []): array
    {
        if(!StringHelper::isNotEmpty($data->getData())) {
            $data = $this->getVendor()->getDownloader()->get($params["url"]);
        }

        if(!StringHelper::isNotEmpty($data->getData())) {
            return [];
        }

        return parent::parseContent($data, $params);
    }

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
            $i++;
            array_push($child, $fi);

        }

        return $child;
    }

    // хелпер методы
    private function parseSrcset(string $srcset)
    {
        $arr = explode(",", $srcset);
        $largestSize = 0;
        $largestSrc = "";
        foreach ($arr as &$str) {
            $array = explode(" ", trim($str));
            $size = intval(substr($array[1],0,-1));
            $src = $array[0];

            if($largestSize < $size) {
                $largestSrc = $src;
                $largestSize = $size;
            }
        }

        return [$largestSrc];
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
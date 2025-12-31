<?php

namespace GoldSalekBot\Handlers;

use GoldSalekBot\Bot;
use GoldSalekBot\Models\Product;

class ProductHandler
{
    private $bot;
    private $productModel;

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->productModel = new Product();
    }

    // This handler can be extended for product-specific operations
    // Currently, product operations are handled in UserHandler and AdminHandler
}


<?php
namespace App\Storage;

use App\Entity\OrderLine;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CartSessionStorage
 * @package App\Storage
 */
class CartSessionStorage
{
    /**
     * CartSessionStorage constructor.
     *
     * @var OrderLine[] $ShoppingCart
     * @return void
     */
    private $productRepository;
    private $session;
    private $shoppingCart;

    public function __construct(RequestStack $requestStack,
                                ProductRepository $productRepository)
    {
        $this->session = $requestStack->getSession();
        $this->productRepository = $productRepository;
        $this->deserializeShoppingCart();
    }
    /**
     * Recover shoppingcart from session.
     * Restore Product object
     * @return void
     */
    private function deserializeShoppingCart():void
    {
        $this->shoppingCart = $this->session->get('cart');
        if($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as &$orderLine) {
                $orderLine = unserialize($orderLine);
                $orderLine->setProduct($this->productRepository->find($orderLine->getProduct()->getId()));
            }
        }
    }

    /**
     * Add product in shoppingcart in session.
     *
     * @return void
     */
    public function addProductToCart(int $product_id): void
    {

        $exist=false;
        //search for orderline with product_id => inc quantity
        if($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as $orderLine) {
                if ($orderLine->getProduct()->getId() == $product_id) {
                    $orderLine->setQuantity($orderLine->getQuantity() + 1);
                    $exist = true;
                }
            }
        }
        //product does not exist -> new OrderLine
        if (!$exist) {
            $newOrderLine=new OrderLine();
            $newOrderLine->setQuantity(1);
            $newOrderLine->setProduct($this->productRepository->find($product_id));
            $this->shoppingCart[]=$newOrderLine;

        }
        $this->serializeShoppingCart();
    }

    private function serializeShoppingCart(){
        $cart=[];
        if($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as $orderLine) {
                $cart[]= serialize($orderLine);
            }
        }
        $this->session->set('cart', $cart);
    }

    /**
     * Get number of products in shoppingcart.
     *
     * @return int
     */
    public function getNumberOfProductInCart(): int
    {
        $amount = 0;

        if ($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as $orderLine) {
                $amount+=$orderLine->getQuantity();
            }
        }
        return $amount;
    }

    /**
     * Get total price in shoppingcart.
     *
     * @return int
     */
    public function getTotalPrice(): int
    {
        $amount = 0;
        if ($this->shoppingCart!=null){
            foreach ($this->shoppingCart as $orderLine) {
                $amount += $orderLine->getProduct()->getPrice();
            }
        }
        return $amount;
    }

    /**
     * Get shoppingcart.
     *
     * @return array|null
     */
    public function getShoppingCart (): array|null
    {
        return $this->shoppingCart;
    }

    /**
     * Clear shoppingcart.
     *
     * @return void
     */
    public function clearShoppingCart(): void
    {
        $this->session->set('cart', []);
        $this->shoppingCart=null;
    }

    /**
     * Remove item from shoppingcart.
     *
     * @return void
     */
    public function removeProductFromCart(int $product_id): void
    {
        if($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as $key=> $orderLine) {
                if ($orderLine->getProduct()->getId() == $product_id) {
                    unset($this->shoppingCart[$key]);
                }
            }
        }
        $this->shoppingCart=array_values($this->shoppingCart);
        $this->serializeShoppingCart();
    }
}

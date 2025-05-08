<?php
namespace App\Storage;

use App\Entity\Order;
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
        $this->getShoppingCartFromSession();
    }
    /**
     * Recover shoppingcart from session.
     * In session de bestelde product_id's bv [3,5,5,6,2]
     * @return void
     *
     */
    private function getShoppingCartFromSession():void
    {
        $this->shoppingCart =[];
        $products = $this->session->get('cart');
        if(!empty($products)) {
            foreach ($products as $product_id) {
                $orderLine=$this->orderLineExist($product_id);
                if($orderLine!=null) {
                    $orderLine->setQuantity($orderLine->getQuantity()+1);
                } else {
                    $orderLine=new OrderLine();
                    $orderLine->setProduct($this->productRepository->find($product_id));
                    $orderLine->setQuantity(1);
                    $this->shoppingCart[] = $orderLine;
                }
            }
        }
    }

    private function orderLineExist(int $product_id):?OrderLine
    {
        foreach ($this->shoppingCart as $orderLine) {
            if ($orderLine->getProduct()->getId() == $product_id) {
                return $orderLine;
            }
        }
        return null;
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
        $this->setShoppingCartToSession();
    }

    private function setShoppingCartToSession():void{
        $cart=[];
        if($this->shoppingCart!=null) {
            foreach ($this->shoppingCart as $orderLine) {
                $amount=$orderLine->getQuantity();
                for($i=0;$i<$amount;$i++){
                    $cart[]=$orderLine->getProduct()->getId();
                }
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
                $amount += $orderLine->getProduct()->getPrice()*$orderLine->getQuantity();
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

}

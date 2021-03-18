<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\Products;
use App\Repository\AddressRepository;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class OrderController
 * @package App\Controller
 * @Route("/api", name="order_api")
 */
class OrderController extends AbstractController
{
    CONST DEFAULT_SHIPPING_DATE_BUFFER = "+2 day";
    /**
     * @param OrdersRepository  $ordersRepository
     * @return JsonResponse
     * @Route("/orders", name="orders", methods={"GET"})
     */
    public function getOrders(OrdersRepository  $ordersRepository){
        $orders =  $ordersRepository->findAllOrders($this->getUser()->getId());

        return $this->response($orders);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductsRepository  $productsRepository
     * @param AddressRepository  $addressRepository
     * @return JsonResponse
     * @throws \Exception
     * @Route("/orders", name="order_add", methods={"POST"})
     */
    public function addOrders(Request $request, EntityManagerInterface $entityManager,
                              ProductsRepository  $productsRepository,
                              AddressRepository $addressRepository){

        try{
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('product_id')){
                throw new \Exception();
            }

            $address = $addressRepository->findOneBy(['user_id'=>$this->getUser()->getId()]);
            $product = $productsRepository->find($request->get('product_id'));

            $now = new \DateTime("now");
            $post = new Orders();
            $post->setAddressId($address->getId());
            $post->setQuantity($product->getPrice());
            $post->setUserId($this->getUser()->getId());
            $post->setProductId($product->getId());
            $post->setShippingDate($now->modify(self::DEFAULT_SHIPPING_DATE_BUFFER));
            $entityManager->persist($post);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'success' => "Order added successfully",
            ];
            return $this->response($data);

        }catch (\Exception $e){
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }

    }


    /**
     * @param OrdersRepository  $ordersRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_get", methods={"GET"})
     */
    public function getOrder(OrdersRepository  $ordersRepository, $id){
        $post =  $ordersRepository->find($id);

        if (!$post){
            $data = [
                'status' => 404,
                'errors' => "Order not found",
            ];
            return $this->response($data, 404);
        }
        return $this->response($post);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param OrdersRepository  $ordersRepository
     * @param ProductsRepository  $productsRepository
     * @param AddressRepository  $addressRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_put", methods={"PUT"})
     */
    public function updateOrder(Request $request, EntityManagerInterface $entityManager,
                                OrdersRepository  $ordersRepository,
                                ProductsRepository  $productsRepository,
                                AddressRepository  $addressRepository,
                                $id){

        try{
            $post =  $ordersRepository->findOneBy(['id'=>$id, 'user_id'=>$this->getUser()->getId()]);

            if (!$post){
                $data = [
                    'status' => 404,
                    'errors' => "Order not found",
                ];
                return $this->response($data, 404);
            }


            if($post->getShippingDate()->diff(new \DateTime('now'))->d < 1) {
                $data = [
                    'status' => 200,
                    'errors' => "Sorry, Shipping Expired",
                ];
                return $this->response($data, 200);
            }

            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('product_id')){
                throw new \Exception();
            }

            $product = $productsRepository->find($request->get('product_id'));
            $address = $addressRepository->findOneBy(['user_id'=>$this->getUser()->getId()]);

            $now = new \DateTime("now");
            $post = new Orders();
            $post->setAddressId($address->getId());
            $post->setQuantity($product->getPrice());
            $post->setProductId($product->getId());
            $post->setUserId($this->getUser()->getId());
            $post->setShippingDate($now->modify(self::DEFAULT_SHIPPING_DATE_BUFFER));
            $entityManager->persist($post);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'errors' => "Order updated successfully",
            ];
            return $this->response($data);

        }catch (\Exception $e){
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }

    }


    /**
     * @param OrdersRepository  $ordersRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_delete", methods={"DELETE"})
     */
    public function deleteProduct(EntityManagerInterface $entityManager, OrdersRepository  $ordersRepository, $id){
        $post =  $ordersRepository->find($id);

        if (!$post){
            $data = [
                'status' => 404,
                'errors' => "Order not found",
            ];
            return $this->response($data, 404);
        }

        $entityManager->remove($post);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Order deleted successfully",
        ];
        return $this->response($data);
    }


    /**
     * Returns a JSON response
     *
     * @param array $data
     * @param $status
     * @param array $headers
     * @return JsonResponse
     */
    public function response($data, $status = 200, $headers = [])
    {
        $serializer = $this->get('serializer');
        $data = $serializer->serialize($data, 'json');

        return new JsonResponse(json_decode($data), $status, $headers);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

}

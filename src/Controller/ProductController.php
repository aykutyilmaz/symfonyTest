<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductController
 * @package App\Controller
 * @Route("/api", name="product_api")
 */
class ProductController extends AbstractController
{
    /**
     * @param ProductsRepository $productRepository
     * @return JsonResponse
     * @Route("/products", name="products", methods={"GET"})
     */
    public function getProducts(ProductsRepository $productRepository){
        $products = $productRepository->findAll();

        return $this->response($products);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductsRepository $productRepository
     * @return JsonResponse
     * @throws \Exception
     * @Route("/products", name="produt_add", methods={"POST"})
     */
    public function addProduct(Request $request, EntityManagerInterface $entityManager, ProductsRepository $productRepository){

        try{
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')){
                throw new \Exception();
            }

            $post = new Products();
            $post->setName($request->get('name'));
            $post->setDescription($request->get('description'));
            $post->setPrice($request->get('price'));
            $post->setCreatedAt($request->get('createdAt'));
            $entityManager->persist($post);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'success' => "Product added successfully",
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
     * @param ProductsRepository $productRepository
     * @param $id
     * @return JsonResponse
     * @Route("/products/{id}", name="products_get", methods={"GET"})
     */
    public function getProduct(ProductsRepository $productRepository, $id){
        $post = $productRepository->find($id);

        if (!$post){
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }
        return $this->response($post);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductsRepository $productRepository
     * @param $id
     * @return JsonResponse
     * @Route("/products/{id}", name="products_put", methods={"PUT"})
     */
    public function updateProduct(Request $request, EntityManagerInterface $entityManager, ProductsRepository $productRepository, $id){

        try{
            $post = $productRepository->find($id);

            if (!$post){
                $data = [
                    'status' => 404,
                    'errors' => "Product not found",
                ];
                return $this->response($data, 404);
            }

            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')){
                throw new \Exception();
            }

            $post->setName($request->get('name'));
            $post->setPrice($request->get('price'));
            $post->setDescription($request->get('description'));
            $entityManager->flush();

            $data = [
                'status' => 200,
                'errors' => "Product updated successfully",
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
     * @param ProductsRepository $productRepository
     * @param $id
     * @return JsonResponse
     * @Route("/products/{id}", name="products_delete", methods={"DELETE"})
     */
    public function deleteProduct(EntityManagerInterface $entityManager, ProductsRepository $productRepository, $id){
        $post = $productRepository->find($id);

        if (!$post){
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }

        $entityManager->remove($post);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Product deleted successfully",
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

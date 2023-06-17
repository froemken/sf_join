<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/sf-join.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\SfJoin\Controller;

use Psr\Http\Message\ResponseInterface;
use StefanFroemken\SfJoin\Domain\Model\Product;
use StefanFroemken\SfJoin\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller for products
 */
class ProductController extends ActionController
{
    protected ProductRepository $productRepository;

    public function injectProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    public function listExtbaseAction(Product $product = null): ResponseInterface
    {
        $this->view->assign('products', $this->productRepository->findWithCategoryExtbase());
        // $this->view->assign('products', $this->productRepository->findWithCategoryExtbaseSolution());
        return new HtmlResponse($this->view->render());
    }

    public function listQueryBuilderExtbaseAction(): ResponseInterface
    {
        $this->view->assign('products', $this->productRepository->findWithCategoryQueryBuilderExtbase());
        // $this->view->assign('products', $this->productRepository->findWithCategoryQueryBuilderExtbaseSolution());
        return new HtmlResponse($this->view->render());
    }

    public function listQueryBuilderPlainAction(): ResponseInterface
    {
        $this->view->assign('products', $this->productRepository->findWithCategoryQueryBuilderPlain());
        return new HtmlResponse($this->view->render());
    }

    public function listContentObjectAction(): ResponseInterface
    {
        $this->view->assign('products', $this->productRepository->findWithCategoryContentObject());
        return new HtmlResponse($this->view->render());
    }
}

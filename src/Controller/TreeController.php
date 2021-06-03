<?php

namespace App\Controller;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TreeController extends AbstractController
{
    #[Route('/tree', name: 'tree', methods: ['GET', 'HEAD'])]
    public function tree(): JsonResponse
    {
        $list = json_decode(file_get_contents('..\list.json'), true);
        $tree = json_decode(file_get_contents('..\tree.json'), true);
        $arrayIterator = new RecursiveArrayIterator($tree);
        $recursiveIterator = new RecursiveIteratorIterator($arrayIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($list as $list) {
            $categoryId = intval($list['category_id']);
            $name = $list['translations']['pl_PL']['name'];
            foreach ($recursiveIterator as $key => $value) {
                if (is_array($value) && array_key_exists('id', $value) && $value['id'] === $categoryId) {
                    $value['name'] = $name;
                    $currentDepth = $recursiveIterator->getDepth();

                    for ($subDepth = $currentDepth; $subDepth >= 0; $subDepth--) {
                        $subIterator = $recursiveIterator->getSubIterator($subDepth);
                        $subIterator->offsetSet($subIterator->key(), (
                        $subDepth === $currentDepth
                            ? $value
                            : $recursiveIterator->getSubIterator(($subDepth + 1))->getArrayCopy()
                        )
                        );
                    }
                }
            }
        }
        return $this->json($recursiveIterator->getArrayCopy());
    }
}

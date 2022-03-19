<?php

namespace App\Controller\Api;

use App\Entity\Color;
use App\Repository\ColorRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ColorApiController extends AbstractController {

    #[Route('/color/new', name: 'color_new', methods: 'POST')]
    #[IsGranted('ROLE_USER', statusCode: 403)]
    public function newColor(
        Request $req,
        ColorRepository $repository,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $errors = [];
        $params = $req->request->all();

        if (!isset($params['_csrf_token'])
            || !$this->isCsrfTokenValid('new-color', $params['_csrf_token'])) {
            $errors[] = $translator->trans('errors.csrf');
        }

        $name = htmlspecialchars($params['color']) ?? null;
        if (!$name || strlen($name) == 0 || strlen($name) >= 255) {
            $errors[] = $translator->trans('errors.new_color_name');
        }

        if (count($errors) === 0) {
            $res = $repository->findBy(['color' => $name]);
            if (count($res) === 0) {
                try {
                    $new_color = new Color();
                    $new_color->setColor($name);
                    $repository->add($new_color);
                } catch (ORMException | OptimisticLockException $e) {
                    $errors[] = $translator->trans('errors.db.insert');
                }
            }
        }

        return $this->json(data: $errors, status: (count($errors) === 0 ? 200 : 400));
    }

    #[Route('/color/json', name: 'color_json', methods: 'GET')]
    public function getColors(ColorRepository $rep): JsonResponse
    {
        return $this->json($rep->findAll());
    }
}
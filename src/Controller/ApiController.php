<?php

namespace App\Controller;

use App\Entity\Color;
use App\Entity\Review;
use App\Entity\Yeti;
use App\Repository\ColorRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Repository\YetiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiController extends AbstractController {

    #[Route('/yeti/get', name: 'get_yeti', methods: 'GET')]
    #[IsGranted('ROLE_USER', statusCode: 403)]
    public function getYeti(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $rev_qb = $em->createQueryBuilder();
        $ids_result = $rev_qb->select('r.id')
            ->from(Review::class, 'r')
            ->where($rev_qb->expr()->gt(
                'r.date',
                time() - (2 * 24 * 60^2)
            ))
            ->andWhere($rev_qb->expr()->eq(
                'r.user',
                $this->getUser()->getId()
            ))
            ->getQuery()
            ->execute();
        $forbidden_ids = [];
        foreach ($ids_result as $id_obj) {
            $forbidden_ids[] = $id_obj['id'];
        }

        return $this->json($forbidden_ids);
    }

    #[Route('/yeti/rate', name: 'rate_yeti', methods: 'POST')]
    #[IsGranted('ROLE_USER', statusCode: 403)]
    public function rateYeti(
        Request $req,
        ReviewRepository $review_repository,
        YetiRepository $yeti_repository
    ): JsonResponse
    {
        $parsed = $req->toArray();
        $errors = [];

        if (!isset($parsed['rating']) || !in_array($parsed['rating'], [-1, 0, 1])) {
            $errors[] = 'errors.rating.value';
        }

        $yeti = null;
        if (isset($parsed['yeti_id'])) $yeti = $yeti_repository->find($parsed['yeti_id']);
        if (!$yeti) $errors[] = 'errors.rating.yeti';


        if (empty($errors)) {
            $new_review = (new Review())
                ->setValue($parsed['rating'])
                ->setDate(new \DateTime())
                ->setYeti($yeti)
                ->setUser($this->getUser());

            try {
                $review_repository->add($new_review);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $this->json($errors, status: (count($errors) === 0 ? 200 : 400));
    }

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
                    $errors[] = 'errors.db.insert';
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
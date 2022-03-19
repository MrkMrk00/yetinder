<?php

namespace App\Controller\Api;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\YetiRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YetiApiController extends AbstractController {

    #[Route('/yeti/get', name: 'get_yeti', methods: 'GET')]
    #[IsGranted('ROLE_USER', statusCode: 403)]
    public function getYeti(Connection $conn): Response {
        $timestamp_from_string = (new \DateTime())
            ->sub(new \DateInterval('P1D'))
            ->format('U');

        $qb0 = $conn->createQueryBuilder();
        $sub_q_forbidden_ids = $qb0
            ->select('r.yeti_id')
            ->distinct()
            ->from('review', 'r')
            ->where('r.date BETWEEN :from AND :to')
            ->andWhere($qb0->expr()->eq('r.user_id', ':userId'));

        $qb1 = $conn->createQueryBuilder();
        $query = $qb1
            ->select('*')
            ->from('yeti', 'y')
            ->leftJoin('y',
                '(' . $this->calculateIndexes($conn)->getSQL() . ')', 'ind',
                'ind.color_id=y.color_id')
            ->where($qb1->expr()->notIn(
                'y.id',
                $sub_q_forbidden_ids->getSQL()
            ))
            ->orderBy('ind.index', 'DESC')
            ->setMaxResults(10)
            ->getSQL();

        try {
            $yetis = $conn->executeQuery($query, [
                'userId' => $this->getUser()->getId(),
                'from' => $timestamp_from_string,
                'to' => time()
            ]);
            $yeti_array = $yetis->fetchAllAssociative();
            $copy = $yeti_array;
            return $this->json(shuffle($yeti_array) ? $yeti_array : $copy);
        } catch (DBALException $e) {
            return new Response(content: $e->getMessage(), status: 500);
        }
    }

    /**
     * Selects color id, color name, sum of review values, count of reviews
     * and index of (sum/count).
     * To execute the connection the 'userId' parameter must be set!
     * @param Connection $conn
     * @return QueryBuilder
     */
    public function calculateIndexes(Connection $conn): QueryBuilder
    {
        /*
         * Select of color_id in 'yeti' with calculated sums of review values
         * and counts by specific users for each color
         */
        $qb0 = $conn->createQueryBuilder();
        $qb0->select('y.color_id', 'SUM(r.value) val', 'COUNT(r.id) \'count\'')
            ->from('review', 'r')
            ->leftJoin('r', 'yeti', 'y', 'r.yeti_id=y.id')
            ->where($qb0->expr()->eq('r.user_id', ':userId'))
            ->groupBy('y.color_id');

        /*
         * Left join prev. query to 'color' in order to have access to color name
         * Calculate index and normalize SUM and COUNT to not be NULL
         */
        $qb1 = $conn->createQueryBuilder();
        return $qb1->select(
            'c.id AS color_id',
            'c.color AS color',
            'COALESCE(sums.val, 0) \'sum\'',
            'COALESCE(sums.count, 0) \'count\'',
            '(COALESCE(sums.val, 0)/COALESCE(sums.count, 1)+1) \'index\'') // ! +1 to not divide by 0 !
            ->from('color', 'c')
            ->leftJoin('c',
                '(' . $qb0->getSQL() . ')', 'sums',
                'sums.color_id=c.id');
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
}
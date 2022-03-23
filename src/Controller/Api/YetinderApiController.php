<?php

namespace App\Controller\Api;

use DateInterval;
use DateTime;
use Exception;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Repository\YetiRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/yetinder', name: 'yetinder_')]
class YetinderApiController extends AbstractController
{
    /**
     * Returns JSON array with 10 yetis, that have not yet been reviewed by user in the past 1 day
     * and sorted by user's preference of yeti colors.
     * @param Connection $conn
     * @return JsonResponse JSON array
     */
    #[Route('/get', name: 'get', methods: 'GET')]
    #[IsGranted('ROLE_USER', statusCode: 403)]
    public function getYeti(Connection $conn): JsonResponse
    {
        $from_date = (new DateTime())
            ->sub(new DateInterval('P1D'))
            ->format('Y-m-d H:i:s');

        // Selects ids of yetis, that the user has reviewed in the last 1 day
        $qb0 = $conn->createQueryBuilder();
        $qb0->select('r.yeti_id')
            ->distinct()
            ->from('review', 'r')
            ->where('r.date BETWEEN :from AND :to')
            ->andWhere('r.user_id = :userId');

        $qb1 = $conn->createQueryBuilder();
        $qb1->select('*')
            ->from('yeti', 'y')
            ->leftJoin('y',
                '(' . $this->calculateIndexes($conn)->getSQL() . ')', 'ind',
                'ind.color_id=y.color_id')
            ->where($qb1->expr()->notIn(
                'y.id',
                $qb0->getSQL()
            ))
            ->orderBy('ind.index', 'DESC');

        try {
            $res = $conn->executeQuery($qb1, [
                'from' => $from_date,
                'to' => (new DateTime())->format('Y-m-d H:i:s'),
                'userId' => $this->getUser()->getId(),
            ])->fetchAllAssociative();

            shuffle($res);
            return $this->json($res);
        } catch (DBALException $e) {
            return $this->json($e->getMessage(), status: 500);
        }
    }

    /**
     * Selects color id, color name, sum of review values, count of reviews
     * and index of (sum/count).
     * To execute the query 'userId' parameter must be set!
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


    #[Route('/rate', name: 'rate', methods: 'POST')]
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
                ->setDate(new DateTime())
                ->setYeti($yeti)
                ->setUser($this->getUser());

            try {
                $review_repository->add($new_review);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $this->json($errors, status: (count($errors) === 0 ? 200 : 400));
    }

    /** @inheritDoc */
    protected function getUser(): ?User
    {
        return parent::getUser();
    }

}
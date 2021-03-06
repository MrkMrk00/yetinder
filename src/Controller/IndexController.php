<?php

namespace App\Controller;

use App\Entity\Color;
use App\Entity\User;
use App\Entity\Yeti;
use App\Repository\UserRepository;
use App\Repository\YetiRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * Renders 10 top-rated yetis
     * @param Request $req
     * @param Connection $connection
     * @return Response
     */
    #[Route('/', name: 'index', methods: 'GET')]
    public function index(Request $req, Connection $connection): Response
    {
        $errors = isset($req->query->all()['errors']) ? $req->query->all()['errors'] : [];

        $qb0 = $connection->createQueryBuilder();
        $qb0->select('r.yeti_id as yeti_id', 'SUM(r.value) AS val', 'COUNT(r.yeti_id) AS cnt')
            ->from('review', 'r')
            ->groupBy('r.yeti_id');

          $qb1 = $connection->createQueryBuilder();
          $qb1->select('*', '(j.val/j.cnt) AS ind')
            ->from('yeti', 'y')
            ->leftJoin('y', '(' . $qb0->getSQL() . ')', 'j', 'j.yeti_id=y.id')
            ->leftJoin('y', 'color', 'c', 'c.id=y.color_id')
            ->where('j.cnt > 3')
            ->orderBy('ind', 'DESC')
            ->setMaxResults(10);

        $yetis = [];
        try {
            $yetis = $connection->fetchAllAssociative($qb1->getSQL());
        } catch (DBALException $e) {
            $errors[] = 'errors.db.fetch';
            $errors[] = $e->getMessage();
        }

        return $this->render('/yeti/best_of.html.twig', [
            'errors' => $errors,
            'active_link' => 'best_of_yeti',
            'yetis' => $yetis,
        ]);
    }

    /**
     * Renders a form for adding a new yeti & handles form submission.
     * @param Request $req
     * @param YetiRepository $yeti_repo
     * @param UserRepository $user_repo
     * @return Response
     */
    #[Route('/new', name: 'new_yeti', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function newYeti(Request $req, YetiRepository $yeti_repo, UserRepository $user_repo): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('index', ['errors' => ['errors.access_denied.login']]);
        }
        $errors = [];
        $yeti = new Yeti();
        $yeti->setCreatedBy($this->getUser());
        $yeti_form = $this->createYetiForm($yeti);
        $yeti_form->handleRequest($req);

        if ($yeti_form->isSubmitted() && $yeti_form->isValid()) {
            try {
                $yeti_repo->add($yeti);
            } catch (OptimisticLockException | ORMException $e) {
                $errors[] = 'Unable to insert object into database';
            }
            if (count($errors) === 0) return $this->redirectToRoute('index');
        }
        foreach ($yeti_form->getErrors() as $er) $errors[] = $er->getMessage();

        return $this->render('/yeti/yeti_new.html.twig', [
            'active_link' => 'yeti_new',
            'errors' => $errors,
            'yeti_form' => $yeti_form->createView(),
        ]);
    }

    public function createYetiForm(Yeti $yeti): FormInterface {
        return $this->createFormBuilder($yeti)
            ->add('name',   TextType::class)
            ->add('sex', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    'male' => 'male',
                    'female' => 'female'
                ]
            ])
            ->add('weight', IntegerType::class)
            ->add('height', IntegerType::class)
            ->add('age',    IntegerType::class)
            ->add('color',  EntityType::class, [
                'class' => Color::class,
                'choice_label' => fn(Color $col) => $col->getColor(),
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
    }

    #[Route('/yetinder', name: 'yetinder', methods: 'GET')]
    #[IsGranted('ROLE_USER')]
    public function yetinder(): Response
    {
        return $this->render('yetinder/yetinder.html.twig', [
            'active_link' => 'yetinder'
        ]);
    }

    #[Route('/yetistics', name: 'yetistics', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function yetistics(Request $req, Connection $conn): Response
    {
        $defaults = [];

        $qb0 = $conn->createQueryBuilder();
        $qb0->select(
            'r.yeti_id',
            'SUM(r.value) rsum',
            'COUNT(r.id) rcount')
            ->from('review', 'r')
            ->groupBy('r.yeti_id');

        if ($from = $req->request->get('from')) {
            if ($timestamp = strtotime($from)) {
                $qb0->andWhere($qb0->expr()->gt(
                    'r.date',
                    '\'' . date('Y-m-d H:i:s', $timestamp) . '\''
                ));
                $defaults['from'] = $from;
            }
        }

        if ($until = $req->request->get('until')) {
            if ($timestamp = strtotime($until)) {
                $qb0->andWhere($qb0->expr()->lt(
                    'r.date',
                    '\'' . date('Y-m-d H:i:s', $timestamp) . '\''
                ));
                $defaults['until'] = $until;
            }
        }

        $qb1 = $conn->createQueryBuilder();
        $qb1->select(
            'y.*',
            'c.color',
            '(COALESCE(rs.rsum, 0)/COALESCE(rs.rcount, 0)) ind',
            'COALESCE(rs.rcount, 0) \'count\'',
            'COALESCE(rs.rsum, 0) \'sum\'')
            ->from('yeti', 'y')
            ->join('y', '(' . $qb0->getSQL() . ')', 'rs', 'rs.yeti_id=y.id')
            ->leftJoin('y', 'color', 'c', 'c.id=y.color_id')
            ->orderBy('sum', 'DESC');

        try {
            $yetis = $conn->executeQuery($qb1->getSQL())->fetchAllAssociative();
        } catch (DBALException $e) {
            return new Response($e->getMessage(), status: 500);
        }

        return $this->render('/yetistics/yetistics.html.twig', [
            'active_link' => 'yetistics',
            'yetis' => $yetis,
            'defaults' => $defaults
        ]);
    }

    /** @inheritDoc */
    protected function getUser(): ?User
    {
        return parent::getUser();
    }
}

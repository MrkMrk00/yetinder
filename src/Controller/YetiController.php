<?php

namespace App\Controller;

use App\Entity\Color;
use App\Entity\Yeti;
use App\Repository\UserRepository;
use App\Repository\YetiRepository;
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

class YetiController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $req, YetiRepository $repository): Response
    {
        $errors = isset($req->query->all()['errors']) ? $req->query->all()['errors'] : [];

        $yetis = $repository->createQueryBuilder('yeti')
            ->orderBy('yeti.name', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->execute();

        return $this->render('/yeti/index.html.twig', [
            'errors' => $errors,
            'active_link' => 'best_of_yeti',
            'yetis' => $yetis,
        ]);
    }

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

        return $this->render('/yeti/routes/yeti_new.html.twig', [
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

    #[Route('/yetinder', name: 'yetinder')]
    #[IsGranted('ROLE_USER')]
    public function yetinder(): Response {
        return $this->render('yeti/routes/yetinder.html.twig', [
            'active_link' => 'yetinder'
        ]);
    }
}
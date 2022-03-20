<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YetisticsController extends AbstractController
{
    #[Route('/yetistics', name: 'yetistics')]
    public function index(): Response
    {
        return $this->render('/yetistics/yetistics.html.twig', [
            'active_link' => 'yetistics'
        ]);
    }
}
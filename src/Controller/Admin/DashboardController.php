<?php

namespace App\Controller\Admin;

use App\Controller\Admin\CrudControllers\ColorCrudController;
use App\Controller\Admin\CrudControllers\ReviewCrudController;
use App\Controller\Admin\CrudControllers\UserCrudController;
use App\Controller\Admin\CrudControllers\YetiCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            $url = $this->generateUrl('admin');
            return $this->redirectToRoute('app_login', ['_target_path' => $url]);
        }
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('index', [
                'errors' => ['errors.access_denied.admin']
            ]);
        }

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Yetinder Admin')
            ->setFaviconPath('/icons/admin.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('To index', 'fa fa-home', '/');
        yield MenuItem::linkToCrud('Users', 'fas fa-user', UserCrudController::getEntityFqcn());
        yield MenuItem::linkToCrud('Colors', 'fas fa-palette', ColorCrudController::getEntityFqcn());
        yield MenuItem::linkToCrud('Yetis', 'fas fa-user', YetiCrudController::getEntityFqcn());
        yield MenuItem::linkToCrud('Reviews', 'fas fa-star', ReviewCrudController::getEntityFqcn());
    }
}

<?php

namespace App\Controller\Admin\CrudControllers;

use App\Entity\Yeti;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class YetiCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Yeti::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}

<?php

namespace App\Controller\Admin\CrudControllers;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use JetBrains\PhpStorm\Pure;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function createEntity(string $entityFqcn) {
        $entity = new Review();
        $entity->setDate(new \DateTime());
        return $entity;
    }


    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('value')
            ->setChoices(['-1' => -1, '0' => 0, '+1' => 1])
            ->renderExpanded();
        yield AssociationField::new('user')
            ->setCrudController(UserCrudController::class);
        yield AssociationField::new('yeti')
            ->setCrudController(YetiCrudController::class);
        yield DateTimeField::new('date');
    }

}

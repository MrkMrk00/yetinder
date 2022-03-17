<?php

namespace App\Controller\Admin\CrudControllers;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable {
        yield TextField::new('email', 'Email')
            ->setRequired(true)
            ->setFormType(EmailType::class);
        yield TextField::new('plain_password', 'Password')
            ->setRequired(true)
            ->setFormType(PasswordType::class)
            ->hideOnIndex()
            ->hideOnDetail();
        yield ArrayField::new('roles', 'Roles');
    }


    private function hashPassword(User $user) {
        $password_hasher_factory = new PasswordHasherFactory([
            User::class => ['algorithm' => 'auto']
        ]);

        $hasher = new UserPasswordHasher($password_hasher_factory);
        $hashed_password = $hasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashed_password);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }
}

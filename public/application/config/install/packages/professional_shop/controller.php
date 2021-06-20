<?php

namespace Application\StartingPointPackage\ProfessionalShop;

use Concrete\Core\Application\Application;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Database\DatabaseStructureManager;
use Concrete\Core\Package\Event\PackageEntities;
use Concrete\Core\Package\Package;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Package\Routine\AttachModeInstallRoutine;
use Concrete\Core\Package\StartingPointInstallRoutine;
use Concrete\Core\Package\StartingPointPackage;
use Concrete\Core\Page\Page;
use Concrete\Core\Validation\CSRF\Token;
use Doctrine\ORM\EntityManagerInterface;

class Controller extends StartingPointPackage
{
    protected $pkgHandle = 'professional_shop';

    public function getPackageName()
    {
        return t("Professional Shop");
    }

    public function getPackageDescription()
    {
        return t("Starting point package to install a professional shop.");
    }

    public function finish()
    {

        $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
        /** @var PackageService $packageService */
        $packageService = $app->make(PackageService::class);
        $pkgClass = Package::getClass("bitter_shop_system");

        if ($pkgClass) {
            $packageService->install($pkgClass, []);
        }

        parent::finish();

        $pev = new PackageEntities();
        $app->make('director')->dispatch('on_refresh_package_entities', $pev);
        $entityManagers = array_merge([$app->make(EntityManagerInterface::class)], $pev->getEntityManagers());

        foreach ($entityManagers as $em) {
            $manager = new DatabaseStructureManager($em);
            $manager->refreshEntities();
        }
    }

    public function install_content()
    {
        /*
         * CIF file format doesn't allow to perform a full content swap.
         * So we need to install this package manually.
         */

        $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
        /** @var PackageService $packageService */
        $packageService = $app->make(PackageService::class);
        /** @var Token $token */
        $token = $app->make(Token::class);

        /** @noinspection PhpDeprecationInspection */
        $pkgClass = Package::getClass("bitter_theme");

        if ($pkgClass) {
            $packageService->install($pkgClass, [
                "pkgDoFullContentSwap" => 1,
                "ccm_token" => $token->generate('install_options_selected')
            ]);
        }

        Page::getByPath("/account/avatar")->delete();
        Page::getByPath("/account/messages")->delete();

        parent::install_content();
    }

}

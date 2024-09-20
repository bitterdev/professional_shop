<?php /** @noinspection PhpUndefinedClassInspection */

namespace Application\StartingPointPackage\ProfessionalShop;

use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportCategoriesRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportCouponsRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportCustomersRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportOrdersRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportPdfEditorRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportProductsRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportShippingCostsRoutine;
use Bitter\BitterShopSystem\Backup\ContentImporter\Importer\Routine\ImportTaxRatesRoutine;
use Bitter\BitterTheme\Backup\ContentImporter\Importer\Routine\ImportMultilingualContentRoutine;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Backup\ContentImporter\Importer\Manager;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\DatabaseStructureManager;
use Concrete\Core\Package\Event\PackageEntities;
use Concrete\Core\Package\Package;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Package\StartingPointPackage;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\BitterShopSystem\Controller as ShopSystemPackageController;
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
        parent::finish();

        $app = Application::getFacadeApplication();

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

        $app = Application::getFacadeApplication();
        /** @var Token $token */
        $token = $app->make(Token::class);
        $app = Application::getFacadeApplication();
        /** @var PackageService $packageService */
        $packageService = $app->make(PackageService::class);
        /** @var Repository $config */
        $config = $app->make(Repository::class);

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

        /** @noinspection PhpDeprecationInspection */
        $pkgClass = Package::getClass("bitter_shop_system");

        if ($pkgClass) {
            $packageService->install($pkgClass, []);

            $pkgEntity = $packageService->getByHandle("bitter_shop_system");
            /** @var ShopSystemPackageController $pkg */
            $pkg = $pkgEntity->getController();

            // Install sample content
            if (is_dir($pkg->getPackagePath() . '/content_files')) {
                $contentImporter = new ContentImporter();
                /** @noinspection PhpUnhandledExceptionInspection */
                $contentImporter->importFiles($pkg->getPackagePath() . '/content_files', true);
            }

            /*
             * need to add all additional import routines here because multiple packages try to extend
             * the 'import/item/manager' singleton.
             */

            /** @var ImporterManager $importer */
            /** @noinspection PhpDeprecationInspection */
            $app->bindshared(
                'import/item/manager',
                function ($app) {
                    /** @var \Concrete\Core\Application\Application $app */
                    /** @var Manager $importer */
                    $importer = $app->make(Manager::class);
                    foreach ($app->make('config')->get('app.importer_routines') as $routine) {
                        $importer->registerImporterRoutine($app->make($routine));
                    }
                    $importer->registerImporterRoutine($app->make(ImportMultilingualContentRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportTaxRatesRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportShippingCostsRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportCategoriesRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportProductsRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportCustomersRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportOrdersRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportCouponsRoutine::class));
                    $importer->registerImporterRoutine($app->make(ImportPdfEditorRoutine::class));

                    return $importer;
                }
            );

            $contentImporter = new ContentImporter();
            $contentImporter->importContentFile($pkg->getPackagePath() . "/content.xml");

            // enable public registration
            $config->save('concrete.user.registration.enabled', true);
            $config->save('concrete.user.registration.type', 'enabled');
        }
    }

}

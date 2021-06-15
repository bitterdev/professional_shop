<?php

namespace Application\StartingPointPackage\ProfessionalShop;

use Concrete\Core\Application\Application;
use Concrete\Core\Backup\ContentImporter;
use Concrete\Core\Package\Routine\AttachModeInstallRoutine;
use Concrete\Core\Package\StartingPointInstallRoutine;
use Concrete\Core\Package\StartingPointPackage;

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

}

<?php
namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController extends BaseController
{
    public function loadLegacyScript(string $requestPath, string $legacyScript)
    {
        return new StreamedResponse(
            function () use ($requestPath, $legacyScript) {
                $_SERVER['PHP_SELF'] = $requestPath;
                $_SERVER['SCRIPT_NAME'] = $requestPath;
                $_SERVER['SCRIPT_FILENAME'] = $legacyScript;

                chdir(dirname($legacyScript));

                include $legacyScript;
            }
        );
    }
}
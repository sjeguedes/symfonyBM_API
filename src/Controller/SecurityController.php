<?php

declare(strict_types=1);

namespace App\Controller;

use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController
 *
 * This is a kind of fake controller to use OpenAPi documentation.
 *
 * @OA\Tag(name="Login - Authentication")
 */
class SecurityController extends AbstractController
{
    /**
     * Login to get a JWT in order to authenticate on request.
     *
     * @ApiDoc\Security()
     *
     * @OA\RequestBody(
     *     description="Partner account credentials",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="api_account",
     *              type="object",
     *              required={"email", "password"},
     *              properties={
     *                   @OA\Property(property="email", type="string"),
     *                   @OA\Property(property="password", type="string")
     *              },
     *              example={"email"="prestataire-359f@marques-sasu.fr", "password"="pass_11"}
     *          )
     *      )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get a JWT on successful operation",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="token",
     *              type="string",
     *              example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MDkwNjY2OTIsImV4cCI6MTYwOTA3MDI5Miwicm9sZXMiOlsiUk9MRV9BUElfQ09OU1VNRVIiLCJST0xFX0FQSV9BRE1JTiJdLCJlbWFpbCI6InByZXN0YXRhaXJlLTM1OWZAbWFycXVlcy1zYXN1LmZyIiwidXVpZCI6Ijk4ZjMzMWQyLTE4ZWItNGU5MS04MTU5LTBiYzJkYzVmMTVjMiJ9.fEZi5sVOmFEvaXVJrBsYQ2GBvjZKkdaYekMPsKt4NROtk_EUfx12kBG5UsDYc-k333T4hgEpoY5OWT2so_GFEQOQ3m1M1KMHVsOSyBtLJKIRmTjSICq1H01hdb-D9m6qg6-ognsjIBrjVZv0SOLWcB9VvxBZJiBwoaYz-HZ6sJESIijlo9qu6s30iXiX50nP-ILbKGFKP2XD7a1JikGTyWs2akgN34uEL7jvVtGgQ_sgKLkXw8kAON4c69LJtPByrGH7cMWf-CdcyVjzmB7nKGGV8rJ55wGMg75neLpoFJj2HyL6qf3SHvNQ2Iqhcif-QZLvh7OgWnmt55GWUqsXwr1ehLgevrGWBG0UQfIhXOItyRCpne2uHMHYiDU4zm7cyRqY5H8gilO_z45JC0XXoZyouZP0MDdowmtApJ9cDQ0PD5WSsct5uLs6ioaxzG8ULI3OB4W3BiGD3QBm7Oi8R9BfFKII_hhHJOJG04hhGIFHyfDkFT3X5Bwxck0_O7txOa1nU-S1aEGFIHzI-DK_4xfHGhYBzc0VBfjmjWBTnWlWfAYTre0JArSjGy-F04Q2O0lFgIrzOlbcAffVGSEGgqS3kn63bZ_SVmwXnndTPacs7EFXmlUgwxraZ-L5dX0cuRnSd_aAZWd1cKeGAxtAPAyp-Xvo3ZNXIxHz2JlnD9A"
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=400,
     *     ref="#/components/responses/jwt_bad_request"
     * )
     * @OA\Response(
     *     response=401,
     *     ref="#/components/responses/jwt_unauthorized"
     * )
     *
     * @Route({
     *     "en": "/login/check"
     * }, name="lexik_login_check", methods={"POST"})
     */
    public function getJWT(): Response
    {
        // This code is not reached!
    }
}

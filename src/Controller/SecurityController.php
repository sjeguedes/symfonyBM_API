<?php

declare(strict_types=1);

namespace App\Controller;

use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController
 *
 * This is a kind of fake controller to use OpenAPi documentation.
 *
 * @ApiDoc\Security()
 *
 * @OA\Response(
 *     response=404,
 *     ref="#/components/responses/not_found"
 * )
 * @OA\Response(
 *     response=500,
 *     ref="#/components/responses/internal"
 * )
 *
 * @OA\Tag(name="Login - Authentication")
 */
class SecurityController extends AbstractController
{
    /**
     * Login to get a JWT (and also a refresh token) in order to authenticate on request.
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
     *     description="Get a JWT on successful operation thanks to partner credentials",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="token",
     *              type="string",
     *              example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MDkwNjY2OTIsImV4cCI6MTYwOTA3MDI5Miwicm9sZXMiOlsiUk9MRV9BUElfQ09OU1VNRVIiLCJST0xFX0FQSV9BRE1JTiJdLCJlbWFpbCI6InByZXN0YXRhaXJlLTM1OWZAbWFycXVlcy1zYXN1LmZyIiwidXVpZCI6Ijk4ZjMzMWQyLTE4ZWItNGU5MS04MTU5LTBiYzJkYzVmMTVjMiJ9.fEZi5sVOmFEvaXVJrBsYQ2GBvjZKkdaYekMPsKt4NROtk_EUfx12kBG5UsDYc-k333T4hgEpoY5OWT2so_GFEQOQ3m1M1KMHVsOSyBtLJKIRmTjSICq1H01hdb-D9m6qg6-ognsjIBrjVZv0SOLWcB9VvxBZJiBwoaYz-HZ6sJESIijlo9qu6s30iXiX50nP-ILbKGFKP2XD7a1JikGTyWs2akgN34uEL7jvVtGgQ_sgKLkXw8kAON4c69LJtPByrGH7cMWf-CdcyVjzmB7nKGGV8rJ55wGMg75neLpoFJj2HyL6qf3SHvNQ2Iqhcif-QZLvh7OgWnmt55GWUqsXwr1ehLgevrGWBG0UQfIhXOItyRCpne2uHMHYiDU4zm7cyRqY5H8gilO_z45JC0XXoZyouZP0MDdowmtApJ9cDQ0PD5WSsct5uLs6ioaxzG8ULI3OB4W3BiGD3QBm7Oi8R9BfFKII_hhHJOJG04hhGIFHyfDkFT3X5Bwxck0_O7txOa1nU-S1aEGFIHzI-DK_4xfHGhYBzc0VBfjmjWBTnWlWfAYTre0JArSjGy-F04Q2O0lFgIrzOlbcAffVGSEGgqS3kn63bZ_SVmwXnndTPacs7EFXmlUgwxraZ-L5dX0cuRnSd_aAZWd1cKeGAxtAPAyp-Xvo3ZNXIxHz2JlnD9A"
     *          ),
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string",
     *              example="66dc395dc0ee9459234ec795420b526c21de21781627d5cf44b01960582f03c1e344a73c19a79ca6ae92cc0295d9241f7a7b5b41ba065b6b832ddf63d6bb5ca2"
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
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/login/check"
     * }, name="lexik_login_check", methods={"POST"})
     */
    public function getJWT(): JsonResponse
    {
        // This code is not reached!
    }

    /**
     * Get a JWT and a new refresh token (single use) from database to simplify authentication.
     *
     * @OA\RequestBody(
     *     description="JWT refresh token",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string",
     *              example="cd58357427f6ce382cc70ffa249392f8d48d70a8ed88a66ca8432e107a94c2b4408284db9ee44058981c2cd4b2b87dab9a390b1c5b77ca856f935999335a8ce2"
     *         )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get a JWT on successful operation thanks to refresh token",
     *     @OA\JsonContent(
     *          @OA\Property(
     *              property="token",
     *              type="string",
     *              example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MDkwNjY2OTIsImV4cCI6MTYwOTA3MDI5Miwicm9sZXMiOlsiUk9MRV9BUElfQ09OU1VNRVIiLCJST0xFX0FQSV9BRE1JTiJdLCJlbWFpbCI6InByZXN0YXRhaXJlLTM1OWZAbWFycXVlcy1zYXN1LmZyIiwidXVpZCI6Ijk4ZjMzMWQyLTE4ZWItNGU5MS04MTU5LTBiYzJkYzVmMTVjMiJ9.fEZi5sVOmFEvaXVJrBsYQ2GBvjZKkdaYekMPsKt4NROtk_EUfx12kBG5UsDYc-k333T4hgEpoY5OWT2so_GFEQOQ3m1M1KMHVsOSyBtLJKIRmTjSICq1H01hdb-D9m6qg6-ognsjIBrjVZv0SOLWcB9VvxBZJiBwoaYz-HZ6sJESIijlo9qu6s30iXiX50nP-ILbKGFKP2XD7a1JikGTyWs2akgN34uEL7jvVtGgQ_sgKLkXw8kAON4c69LJtPByrGH7cMWf-CdcyVjzmB7nKGGV8rJ55wGMg75neLpoFJj2HyL6qf3SHvNQ2Iqhcif-QZLvh7OgWnmt55GWUqsXwr1ehLgevrGWBG0UQfIhXOItyRCpne2uHMHYiDU4zm7cyRqY5H8gilO_z45JC0XXoZyouZP0MDdowmtApJ9cDQ0PD5WSsct5uLs6ioaxzG8ULI3OB4W3BiGD3QBm7Oi8R9BfFKII_hhHJOJG04hhGIFHyfDkFT3X5Bwxck0_O7txOa1nU-S1aEGFIHzI-DK_4xfHGhYBzc0VBfjmjWBTnWlWfAYTre0JArSjGy-F04Q2O0lFgIrzOlbcAffVGSEGgqS3kn63bZ_SVmwXnndTPacs7EFXmlUgwxraZ-L5dX0cuRnSd_aAZWd1cKeGAxtAPAyp-Xvo3ZNXIxHz2JlnD9A"
     *          ),
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string",
     *              example="66dc395dc0ee9459234ec795420b526c21de21781627d5cf44b01960582f03c1e344a73c19a79ca6ae92cc0295d9241f7a7b5b41ba065b6b832ddf63d6bb5ca2"
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=401,
     *     ref="#/components/responses/jwt_refresh_unauthorized"
     * )
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/token/refresh"
     * }, name="gesdinet_jwt_refresh_token", methods={"POST"})
     */
    public function getJWTRefreshToken(): Response
    {
        // Return the same response as "Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken::refresh" method
        // with forwarding shortcut and service id using single ":"!
        return $this->forward('gesdinet.jwtrefreshtoken:refresh');
    }
}

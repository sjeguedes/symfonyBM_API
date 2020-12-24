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
     *              example={"email"="name@domain.tld", "password"="my_super_pass1"}
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Get a JWT on successful operation",
     *     @OA\MediaType(
     *         mediaType="application/json"
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Invalid request JSON string content",
     *     @OA\MediaType(
     *         mediaType="application/json"
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Invalid account credentials",
     *     @OA\MediaType(
     *         mediaType="application/json"
     *     )
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

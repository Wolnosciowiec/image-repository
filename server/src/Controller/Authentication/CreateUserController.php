<?php declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Controller\BaseController;
use App\Domain\Authentication\ActionHandler\UserCreationHandler;
use App\Domain\Authentication\Factory\Context\SecurityContextFactory;
use App\Domain\Authentication\Form\AuthForm;
use App\Infrastructure\Common\Http\JsonFormattedResponse;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class CreateUserController extends BaseController
{
    private UserCreationHandler $handler;
    private SecurityContextFactory $authFactory;

    public function __construct(UserCreationHandler $handler, SecurityContextFactory $authFactory)
    {
        $this->handler = $handler;
        $this->authFactory = $authFactory;
    }

    /**
     * Create a new user, assign roles, set optional expiration, upload policy
     *
     * @SWG\Post(
     *     description="Request to create a new access token",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         description="JSON payload",
     *
     *         @SWG\Schema(
     *             type="object",
     *             required={"id", "roles", "data"},
     *             @SWG\Property(property="id", example="ca6a2635-d2cb-4682-ba81-3879dd0e8a77", type="string"),
     *             @SWG\Property(property="password", example="aNti_cap.italiSM", type="string"),
     *             @SWG\Property(property="email", example="example@riseup.net", type="string"),
     *             @SWG\Property(property="about", example="A member of the collective. Technically website administrator.", type="string"),
     *             @SWG\Property(property="organization", example="Food not bombs", type="string"),
     *             @SWG\Property(property="roles", example={"collections.create_new", "collections.manage_tokens_in_allowed_collections"}, type="array", @SWG\Items(type="string")),
     *             @SWG\Property(property="expires", type="string", example="2021-05-01 01:06:01"),
     *             @SWG\Property(property="data", ref=@Model(type=\App\Domain\Authentication\Entity\Docs\TokenData::class))
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response="201",
     *     description="User was successfuly created",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="status",
     *             type="boolean",
     *             example="true"
     *         ),
     *         @SWG\Property(
     *             property="errors",
     *             type="array",
     *             @SWG\Items(
     *                 type="string"
     *             )
     *         ),
     *         @SWG\Property(
     *             property="message",
     *             type="string",
     *             example="User created"
     *         ),
     *         @SWG\Property(
     *             property="token",
     *             ref=@Model(type=\App\Domain\Authentication\Entity\Docs\Token::class)
     *         ),
     *          @SWG\Property(
     *             property="context",
     *             type="array",
     *             @SWG\Items(type="string")
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonFormattedResponse
     *
     * @throws Exception
     */
    public function generateAction(Request $request): Response
    {
        /**
         * @var AuthForm
         */
        $form = $this->decodeRequestIntoDTO($request, AuthForm::class);

        return new JsonFormattedResponse(
            $this->handler->handle(
                $form,
                $this->authFactory->createFromUserAccount($this->getLoggedUser())
            ),
            JsonFormattedResponse::HTTP_CREATED
        );
    }
}
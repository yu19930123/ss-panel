<?php

namespace App\Controllers;

use Slim\Http\Response;
use App\Services\Factory;
use Interop\Container\ContainerInterface;
use Pongtan\Http\Controller;
use Pongtan\View\ViewTrait;
use App\Models\User;

/**
 * BaseController.
 */
class BaseController extends Controller
{
    use ViewTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var User
     */
    protected $user;

    public function __construct(ContainerInterface $ci)
    {
        $this->logger = Factory::getLogger();
        parent::__construct($ci);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return user();
    }

    /**
     * @param Response $response
     * @param $data
     * @param int $statusCode
     * @return mixed
     */
    public function echoJsonWithData(Response $response, $data = [], $statusCode = 200)
    {
        return $this->echoJson($response, [
            'data' => $data,
        ], $statusCode);
    }
}

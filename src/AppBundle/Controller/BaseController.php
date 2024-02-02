<?php

namespace AppBundle\Controller;

use AppBundle\Repository\ProgrammerRepository;
use AppBundle\Repository\UserRepository;
use AppBundle\Repository\ProjectRepository;
use AppBundle\Repository\BattleRepository;
use AppBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializationContext;

abstract class BaseController extends Controller
{
    /**
     * Is the current user logged in?
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->container->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * Logs this user into the system
     *
     * @param User $user
     */
    public function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
    }

    public function addFlash($message, $positiveNotice = true)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $noticeKey = $positiveNotice ? 'notice_happy' : 'notice_sad';

        $request->getSession()->getFlashbag()->add($noticeKey, $message);
    }

    /**
     * Used to find the fixtures user - I use it to cheat in the beginning
     *
     * @param $username
     * @return User
     */
    public function findUserByUsername($username)
    {
        return $this->getUserRepository()->findUserByUsername($username);
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:User');
    }

    /**
     * @return ProgrammerRepository
     */
    protected function getProgrammerRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Programmer');
    }

    /**
     * @return ProjectRepository
     */
    protected function getProjectRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Project');
    }

    /**
     * @return BattleRepository
     */
    protected function getBattleRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:Battle');
    }

    /**
     * @return \AppBundle\Battle\BattleManager
     */
    protected function getBattleManager()
    {
        return $this->container->get('battle.battle_manager');
    }

    /**
     * @return ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->getDoctrine()
            ->getRepository('AppBundle:ApiToken');
    }

    // baseController extends all the controller 
    // When we use response, the Content-Type become text/html 
    // solution : centralize serialize() and then creating the Response in every controller
    protected function createApiResponse($data, $statusCode = 200)
    {
        // 1° serialize datas
        $json = $this->serialize($data);
        // 2° return response with content-type
        return new Response($json, $statusCode, array( 
            'Content-Type' => 'application/json'
        ));
    }

    protected function serialize($data, $format = 'json')
    {
        // So the serializer works like this: you give it an object, and it serializes every property on it. 
        // Yep, you can control that - just hang on a few minutes. 
        // But, if any of these properties is null , instead of returning that key with null , it omits it entirely.
        $context = new SerializationContext(); 
        $context->setSerializeNull(true);

        // Setting the SerializationGroup
        $request = $this->get('request_stack')->getCurrentRequest();
        $groups = array('Default');

        if ($request->query->get('deep')) {
            $groups[] = 'deep'; 
        }
        
        $context->setGroups($groups);

        return $this->container->get('jms_serializer') 
            ->serialize($data, $format, $context);
    }
}
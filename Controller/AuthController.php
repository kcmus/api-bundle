<?php

namespace RJP\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use RJP\ApiBundle\Contracts as Contracts;
use Symfony\Component\HttpFoundation\Request;

use RJP\ApiBundle\Entity\Token;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthController extends ApiController
{
    /**
     * Authenticate a user and return a authorization token. This token must be passed with each subsequent request.
     *
     * @Post("/auth")
     *
     * @View()
     * @ApiDoc(
     *  input= {
     *      "class" = "RJP\ApiBundle\Contracts\Auth",
     *      "groups" = {"Post"}
     *  },
     *  output="RJP\ApiBundle\Contracts\Auth"
     * )
     */
    public function postAuthAction(Request $request)
    {
        try
        {
            if (!$this->getApi()->isValid())
            {
                return $this->view(
                    $this->getApi()->serialize($this->getApi()->getApiRequestErrors()),
                    400
                );
            }

            $user = $this->getDoctrine()->getRepository('RJPApiBundle:User')->findOneBy(
                array('email' => $this->getApi()->getApiRequest()->getUsername())
            );

            if (empty($user))
            {
                // Temporary until I can get a custom exception handler setup
                throw new AccessDeniedException();
            }
            else
            {
                if ($user->getPassword() != md5($user->getGuid().":".$this->getApi()->getApiRequest()->getPassword()))
                {
                    // Temporary until I can get a custom exception handler setup
                    throw new AccessDeniedException();
                }
            }

            $existingToken = $this->getDoctrine()->getRepository('RJPApiBundle:Token')->findOneBy(
                array('user' => $user)
            );

            // Create a unique token with mcrypt
            if (empty($existingToken))
            {
                // Create a random 32 character security token.
                // This logic will need cleaned up, this is just temporary.
                $token = bin2hex(mcrypt_create_iv(32, MCRYPT_RAND));

                // Save that new token to the user
                $userToken = new Token();
                $userToken->setUser($user);
                $userToken->setToken($token);
                $this->getDoctrine()->getManager()->persist($userToken);
                $this->getDoctrine()->getManager()->flush();
            }
            else
            {
                $token = $existingToken->getToken();
            }

            // Delete any existing tokens for the user - this isn't complete, it lacks timestamps, etc
            //$qb = $this->getDoctrine()->getRepository('RJPApiBundle:Token')->createQueryBuilder('ut');
            //$qb->delete()->andWhere($qb->expr()->eq('ut.user', ':user'))->setParameter(':user', $user);
            //$qb->getQuery()->execute();
            // Persist the change
            //$this->getDoctrine()->getManager()->flush();

            // Construct our return object
            $response = new Contracts\Auth();
            $response->setToken($token);

            // Done
            return $response;
        }
        catch (\Exception $e)
        {
            throw new \Exception('unable_to_auth');
        }
    }
}

<?php

namespace RJP\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use RJP\ApiBundle\Contracts as Contracts;

use RJP\ApiBundle\Entity\Player;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlayerController extends ApiController
{
    /**
     * Create a new player.
     *
     * @View(serializerGroups={"Default", "Embedded", "Links"})
     * @ApiDoc(
     *  output= {
     *      "class" = "RJP\ApiBundle\Contracts\Player",
     *      "groups" = {"Default", "Embedded", "Links"}
     *  },
     *  input= {
     *      "class" = "RJP\ApiBundle\Contracts\Player",
     *      "groups" = {"Post"}
     *  }
     * )
     */
    public function postPlayerAction()
    {
        try
        {
            // New Player
            $player = new Player();
            // Tell doctrine we need to watch this object since it's new
            $this->getEm()->persist($player);
            // Map incoming data to a Player object;
            $this->getApi()->setData($player);

            if ($this->getApi()->isValid())
            {
                // Persist changes to the database
                $this->getEm()->flush();
            }
            else
            {
                // Return a 400, bad request
                return $this->view(
                    $this->getApi()->serialize(
                        $this->getApi()->getApiRequestErrors()
                    ),
                    400
                );
            }

            return $this->getApi()->serialize($player);
        }
        catch (\Exception $e)
        {
            throw new \Exception("player_unable_post");
        }
    }

    /**
     * Partially update a player.
     *
     * @View(serializerGroups={"Default", "Embedded", "Links"})
     * @ApiDoc(
     *  output= {
     *      "class" = "RJP\ApiBundle\Contracts\Player",
     *      "groups" = {"Default", "Embedded", "Links"}
     *  },
     *  input= {
     *      "class" = "RJP\ApiBundle\Contracts\Player",
     *      "groups" = {"Patch"}
     *  }
     * )
     */
    public function patchPlayerAction($playerGuid)
    {
        try
        {
            $player = $this->getRepo('RJPApiBundle:Player')->findOneBy(
                array(
                    'guid' => $playerGuid
                )
            );

            $this->getApi()->setData($player);

            if ($this->getApi()->isValid())
            {
                // Persist changes to the database
                $this->getEm()->flush();
            }
            else
            {
                // Return a 400, bad request
                return $this->view(
                    $this->getApi()->serialize(
                        $this->getApi()->getApiRequestErrors()
                    ),
                    400
                );
            }

            return $this->getApi()->serialize($player);
        }
        catch (\Exception $e)
        {
            throw new \Exception("player_unable_patch");
        }
    }

    /**
     * Delete a player by guid.
     *
     * @View(statusCode=200)
     * @ApiDoc()
     */
    public function deletePlayerAction($playerGuid)
    {
        try
        {
            $player = $this->getRepo('RJPApiBundle:Player')->findOneBy(
                array(
                    'guid' => $playerGuid
                )
            );

            $this->getEm()->remove($player);
            $this->getEm()->flush();
        }
        catch (\Exception $e)
        {
            throw new \Exception("player_unable_delete");
        }
    }

    /**
     * Create a new player configuration. Not implemented.
     */
    public function postPlayerConfigurationsAction($playerGuid)
    {
    }

    /**
     * Get a player by GUID.  This pulls from Elasticsearch.
     *
     * @View(serializerGroups={"Default", "Embedded", "Links"})
     * @ApiDoc(
     *  output= {
     *      "class" = "RJP\ApiBundle\Contracts\Player",
     *      "groups" = {"Default", "Embedded", "Links"}
     *  },
     *  requirements={
     *    {
     *      "name"="playerGuid",
     *      "dataType"="string",
     *      "requirement"="(\w|-)+",
     *      "description"="Player GUID"
     *    }
     *  }
     * )
     */
    public function getPlayerAction($playerGuid)
    {
        try
        {
            $finder = $this->container->get('fos_elastica.index.search.player');

            // Create a new Elasticsearch query
            $fieldQuery = new \Elastica\Query\Match();
            $fieldQuery->setFieldQuery('guid', $playerGuid);

            $players = $finder->search($fieldQuery);

            return $this->getApi()->serialize($players[0]->getHit()["_source"]);
        }
        catch (\Exception $e)
        {
            throw new \Exception("player_unable_get");
        }
    }

    /**
     * Get a player configuration by GUID.  This pulls directly from the database.
     *
     * @View(serializerGroups={"Default", "Embedded", "Links"})
     * @ApiDoc(
     *  output= {
     *      "class" = "RJP\ApiBundle\Contracts\PlayerConfiguration",
     *      "groups" = {"Default", "Embedded", "Links"}
     *  },
     *  requirements={
     *    {
     *      "name"="playerGuid",
     *      "dataType"="string",
     *      "requirement"="(\w|-)+",
     *      "description"="Player GUID"
     *    },
     *    {
     *      "name"="playerConfigurationGuid",
     *      "dataType"="string",
     *      "requirement"="(\w|-)+",
     *      "description"="Player configuration GUID"
     *    }
     *  }
     * )
     */
    public function getPlayerConfigurationAction($playerGuid, $playerConfigurationGuid)
    {
        try
        {
            // Get the player
            // This one is somewhat unnecessary..since the ownership for this is backwards in our existing db
            $playerConfiguration = $this->getRepo('RJPApiBundle:PlayerConfiguration')->findOneBy(
                array(
                    'guid' => $playerConfigurationGuid
                )
            );

            if (!$this->getApi()->canAdminister($playerConfiguration->getPlayer()->getClient()))
            {
                throw new AccessDeniedHttpException();
            }

            return $this->getApi()->serialize($playerConfiguration);
        }
        catch (\Exception $e)
        {
            throw new \Exception("player_configuration_unable_get");
        }
    }
}

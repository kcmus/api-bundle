<?php

namespace RJP\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;

/**
 * Class ApiController
 * @package RJP\ApiBundle\Controller
 */
class ApiController extends FOSRestController
{
    /**
     * @var \RJP\ApiBundle\Services\Api
     */
    protected $api;

    /**
     * @return \RJP\ApiBundle\Services\Api
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Doctrine entity manger
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Sets the doctrine entity manager
     *
     * @param \Doctrine\ORM\EntityManager $em Doctrine entity manager
     */
    public function setEm(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns the doctrine entity manager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * Returns the doctrine repository
     *
     * @param string $repository Repository to load
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepo($repository)
    {
        return $this->getDoctrine()->getRepository($repository);
    }

    /**
     * Sets the API service in the controller
     *
     * @param \RJP\ApiBundle\Services\Api $api
     */
    public function setApi(\RJP\ApiBundle\Services\Api $api)
    {
        $this->api = $api;
    }
}
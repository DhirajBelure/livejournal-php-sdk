<?php
/**
 * Part of the LiveJournal PHP SDK
 *
 * @author   Konstantin Kuklin <konstantin.kuklin@gmail.com>
 * @license  MIT
 */

namespace LiveJournal;

/**
 * Interface ClientInterface
 */
interface ClientInterface
{
    /**
     * @param array $options
     *
     * @return mixed
     */
    public function login(array $options);

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function getchallenge(array $options);

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function getevents(array $options);

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function postevent(array $options);

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function editevent(array $options);
}
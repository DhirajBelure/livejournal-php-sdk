<?php
/**
 * Part of the LiveJournal PHP SDK
 *
 * @author   Konstantin Kuklin <konstantin.kuklin@gmail.com>
 * @license  MIT
 */
namespace LiveJournal;

use XML_RPC2_Client;

/**
 * Class Client
 *
 * LiveJournal api client
 */
class Client
{
    /**
     * Max posts to fetch
     */
    const MAX_POSTS_TO_FETCH = 50;

    /**
     * Default posts to fetch
     */
    const DEFAULT_POSTS_TO_FETCH = 10;

    /**
     * Default lifejournal server
     */
    const XML_RPC_SERVER = 'www.livejournal.com';

    /**
     * Default livejournal rpc path
     */
    const XML_RPC_PATH = '/interface/xmlrpc';

    /**
     * RPC prefix
     */
    const PREFIX = 'LJ.XMLRPC.';

    /**
     * @var XML_RPC2_Client|ClientInterface
     */
    private $client;

    /**
     * @var XML_RPC2_Client|ClientInterface
     */
    private $clientHttps;

    /**
     * @var string
     */
    private $passwordHexed;

    /**
     * @var string
     */
    private $userName;

    /**
     * Constructor for the LiveJournal
     *
     * @param string      $userName      The username of the blog account
     * @param string      $passwordPlain The password of the blog account
     * @param string|null $server        The URI of the server to connect to
     *
     * @throws \Exception
     */
    public function __construct($userName, $passwordPlain, $server = null)
    {
        $this->userName = $userName;
        $this->passwordHexed = $this->md5hex($passwordPlain);
        // clear plain password
        unset($passwordPlain);

        if ($server === null) {
            $server = self::XML_RPC_SERVER;
        }

        $rpcUrl = $server . self::XML_RPC_PATH;

        $this->client = XML_RPC2_Client::create('http://' . $rpcUrl, array('prefix' => self::PREFIX));
        $this->clientHttps = XML_RPC2_Client::create('https://' . $rpcUrl, array('prefix' => self::PREFIX));

        $challenge = $this->fetchChallenge();

        $loginOptions = array(
            'username' => $userName,
            'clientversion' => 'PHP/livejournal-php-sdk',
            'auth_challenge' => $challenge['challenge'],
            'auth_response' => $challenge['response'],
            'auth_method' => 'challenge'
        );

        $userInfo = $this->client->login($loginOptions);
        if (!isset($userInfo['username']) || $userInfo['username'] !== $userName) {
            throw new \Exception('Something goes wrong on login');
        }
    }

    /**
     * Fetch post
     *
     * @param int $id Post id
     *
     * @return Post|null
     */
    public function fetchPost($id)
    {
        $challenge = $this->fetchChallenge();

        $value = array(
            'username' => $this->userName,
            'auth_method' => 'challenge',
            'auth_challenge' => $challenge['challenge'],
            'auth_response' => $challenge['response'],
            'selecttype' => 'one',
            'itemid' => $id
        );

        $eventList = $this->client->getevents($value);

        $post = null;
        if (count($eventList['events']) !== 0) {
            $post = PostHelper::bindPost($eventList['events'][0]);
        }

        return $post;
    }


    /**
     * Returns an array of recent posts
     *
     * @param string|null $journalName Name of journal
     * @param int         $number      The number of posts to be retrieved.
     *
     * @return Post[]
     */
    public function fetchRecentPosts($journalName = null, $number = self::DEFAULT_POSTS_TO_FETCH)
    {
        if ($number > self::MAX_POSTS_TO_FETCH) {
            $number = self::MAX_POSTS_TO_FETCH;
        }

        $challenge = $this->fetchChallenge();

        $value = array(
            'username' => $this->userName,
            'auth_method' => 'challenge',
            'auth_challenge' => $challenge['challenge'],
            'auth_response' => $challenge['response'],
            'selecttype' => 'lastn',
            'howmany' => $number,
            'prefersubject' => false,
            'noprops' => false,
            'ver' => 1,
            'lineendings' => 'unix',
            'notags' => false,
        );

        if ($journalName) {
            $value['usejournal'] = $journalName;
        }

        $arData = $this->client->getevents($value);

        $postListGroupedById = array();
        foreach ($arData['events'] as $event) {
            $post = PostHelper::bindPost($event);
            $postListGroupedById[$post->id] = $post;
        }

        return $postListGroupedById;
    }

    /**
     * Fetch challenge
     *
     * @return array
     */
    private function fetchChallenge()
    {
        //get challenge for authentication
        $challengeRaw = $this->clientHttps->getchallenge(array());

        $challenge = array(
            'challenge' => $challengeRaw['challenge'],
            'response' => $this->md5hex(
                $challengeRaw['challenge'] . $this->passwordHexed
            )
        );

        return $challenge;
    }

    /**
     * Creates md5 hash of the given string and converts it to hexadecimal representation.
     *
     * @param string $string Some string value
     *
     * @return string md5-hashed hecadecimal representation
     */
    private function md5hex($string)
    {
        $md5 = md5($string, true);
        $hex = '';
        for ($nC = 0, $md5Max = strlen($md5); $nC < $md5Max; $nC++) {
            $hex .= str_pad(dechex(ord($md5[$nC])), 2, '0', STR_PAD_LEFT);
        }

        return $hex;
    }
}
<?php
/**
 * Part of the LiveJournal PHP SDK
 *
 * @author   Konstantin Kuklin <konstantin.kuklin@gmail.com>
 * @license  MIT
 */

namespace LiveJournal;

/**
 * Class PostHelper
 */
class PostHelper
{
    /**
     * Bind array to Post object
     *
     * @param array $event
     *
     * @return Post
     */
    public static function bindPost(array $event)
    {
        $post = new Post();

        $post->canBeCommented = $event['can_comment'];
        $post->url = $event['url'];
        $post->createdDate = $event['logtime'];

        if (!empty($event['repost'])) {
            // for repost
            $post->id = $event['repost_ditemid'];
            $post->author = $event['journalname'];

            $post->authorId = $event['ownerid'];
            $post->authorImage = $event['poster_userpic_url'];
            $post->reposterName = $event['repostername'];
        } else {
            // for post
            $post->id = $event['ditemid'];
            $matches = array();
            preg_match_all('/http:\/\/([\w]+).livejournal.com/i', $event['url'], $matches);
            $post->author = $matches[1][0];
        }

        if (isset($event['props']['taglist']->scalar)) {
            $post->tagList = explode(',', $event['props']['taglist']->scalar);
            $post->tagList = array_filter(
                array_map('trim', $post->tagList),
                function ($tag) {
                    return $tag;
                }
            );
        } else {
            $post->tagList = [];
        }

        if (isset($event['subject']->scalar)) {
            $post->title = $event['subject']->scalar;
        } elseif (isset($event['subject']) && is_string($event['subject'])) {
            $post->title = $event['subject'];
        } else {
            $post->title = '';
        }

        if (isset($event['event']->scalar)) {
            $post->body = $event['event']->scalar;
        }

        return $post;
    }
}
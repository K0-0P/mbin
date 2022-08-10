<?php declare(strict_types=1);

namespace App\Factory\ActivityPub;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Entity\Post;
use App\Service\ActivityPub\ApHttpClient;
use App\Service\ActivityPub\Wrapper\ImageWrapper;
use App\Service\ActivityPub\Wrapper\MentionsWrapper;
use App\Service\ActivityPub\Wrapper\TagsWrapper;
use DateTimeInterface;

class PostNoteFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private PersonFactory $personFactory,
        private GroupFactory $groupFactory,
        private ImageWrapper $imageWrapper,
        private TagsWrapper $tagsWrapper,
        private MentionsWrapper $mentionsWrapper,
        private ApHttpClient $client
    ) {
    }

    public function create(Post $post): array
    {

        $note = [
            'type'            => 'Note',
            '@context'        => [ActivityPubActivityInterface::CONTEXT_URL, ActivityPubActivityInterface::SECURITY_URL],
            'id'              => $this->getActivityPubId($post),
            'attributedTo'    => $post->apId ? $post->user->apProfileId : $this->personFactory->getActivityPubId($post->user),
            'inReplyTo'       => null,
            'to'              => [
                ActivityPubActivityInterface::PUBLIC_URL,
            ],
            'cc'              => [
                $this->groupFactory->getActivityPubId($post->magazine),
                $post->apId
                    ? $this->client->getActorObject($post->user->apProfileId)['followers']
                    : $this->urlGenerator->generate(
                    'ap_user_followers',
                    ['username' => $post->user->username],
                    UrlGeneratorInterface::ABS_URL
                ),
            ],
            'content'         => $post->body,
            'mediaType'       => 'text/html',
            'url'             => $this->getActivityPubId($post),
            'tag'             => $this->tagsWrapper->build($post->tags) + $this->mentionsWrapper->build($post->mentions),
            'commentsEnabled' => true,
            'sensitive'       => $post->isAdult(),
            'published'       => $post->createdAt->format(DateTimeInterface::ISO8601),
        ];

        if ($post->image) {
            $note = $this->imageWrapper->build($note, $post->image, $post->getShortTitle());
        }

        return $note;
    }

    public function getActivityPubId(Post $post): string
    {
        return $post->apId ?? $this->urlGenerator->generate(
                'ap_post',
                ['magazine_name' => $post->magazine->name, 'post_id' => $post->getId()],
                UrlGeneratorInterface::ABS_URL
            );
    }
}

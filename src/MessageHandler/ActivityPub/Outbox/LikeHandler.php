<?php declare(strict_types=1);

namespace App\MessageHandler\ActivityPub\Outbox;

use App\Factory\ActivityPub\ActivityFactory;
use App\Message\ActivityPub\Outbox\DeliverMessage;
use App\Message\ActivityPub\Outbox\LikeMessage;
use App\Repository\UserRepository;
use App\Service\ActivityPub\Wrapper\LikeWrapper;
use App\Service\ActivityPub\Wrapper\UndoWrapper;
use App\Service\ActivityPubManager;
use App\Service\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class LikeHandler implements MessageHandlerInterface
{
    public function __construct(
        private UserRepository $repository,
        private EntityManagerInterface $entityManager,
        private LikeWrapper $likeWrapper,
        private UndoWrapper $undoWrapper,
        private ActivityPubManager $activityPubManager,
        private ActivityFactory $activityFactory,
        private MessageBusInterface $bus,
        private SettingsManager $settingsManager,
    ) {
    }

    #[ArrayShape([
        '@context' => "string",
        'id' => "string",
        'actor' => "string",
        'object' => "string",
    ])] public function __invoke(
        LikeMessage $message
    ): void {
        if (!$this->settingsManager->get('KBIN_FEDERATION_ENABLED')) {
            return;
        }

        $user = $this->repository->find($message->userId);
        $object = $this->entityManager->getRepository($message->objectType)->find($message->objectId);

        $activity = $this->likeWrapper->build(
            $this->activityPubManager->getActorProfileId($user),
            $this->activityFactory->create($object),
        );

        if ($message->removeLike) {
            $activity = $this->undoWrapper->build($activity);
        }

        $followers = $this->repository->findAudience($user);
        foreach ($followers as $follower) {
            $this->bus->dispatch(new DeliverMessage($follower->apProfileId, $activity));
        }
    }
}
<?php declare(strict_types=1);

namespace App\MessageHandler\ActivityPub\Outbox;

use ApiPlatform\Api\UrlGeneratorInterface;
use App\Message\ActivityPub\Outbox\DeliverMessage;
use App\Message\ActivityPub\Outbox\UpdateMessage;
use App\Repository\MagazineRepository;
use App\Repository\UserRepository;
use App\Service\ActivityPub\Wrapper\CreateWrapper;
use App\Service\ActivityPubManager;
use App\Service\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateHandler implements MessageHandlerInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private UserRepository $userRepository,
        private MagazineRepository $magazineRepository,
        private CreateWrapper $createWrapper,
        private EntityManagerInterface $entityManager,
        private ActivityPubManager $activityPubManager,
        private SettingsManager $settingsManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function __invoke(UpdateMessage $message): void
    {
        if (!$this->settingsManager->get('KBIN_FEDERATION_ENABLED')) {
            return;
        }

        $entity = $this->entityManager->getRepository($message->type)->find($message->id);

        $activity = $this->createWrapper->build($entity);
        $activity['id'] = $this->urlGenerator->generate(
            'ap_object',
            ['id' => Uuid::v4()->toRfc4122()],
            \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
        );
        $activity['type'] = 'Update';

        $this->deliver($this->userRepository->findAudience($entity->user), $activity);
        $this->deliver($this->activityPubManager->createCcFromObject($activity, $entity->user), $activity);
        $this->deliver($this->magazineRepository->findAudience($entity->magazine), $activity);
    }

    private function deliver(array $followers, array $activity)
    {
        foreach ($followers as $follower) {
            if (is_string($follower)) {
                $this->bus->dispatch(new DeliverMessage($follower, $activity));

                return;
            }

            $this->bus->dispatch(new DeliverMessage($follower->apProfileId, $activity));
        }
    }
}

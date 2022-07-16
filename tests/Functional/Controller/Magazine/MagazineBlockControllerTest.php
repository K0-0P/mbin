<?php declare(strict_types=1);

namespace App\Tests\Functional\Controller\Magazine;

use App\Service\MagazineManager;
use App\Tests\WebTestCase;

class MagazineBlockControllerTest extends WebTestCase
{
    public function testUserCanBlockAndUnblockMagazine(): void // @todo
    {
        $client  = $this->createClient();
        $manager = static::getContainer()->get(MagazineManager::class);
        $client->loginUser($user = $this->getUserByUsername('JohnDoe'));

        $user2 = $this->getUserByUsername('JaneDoe');
        $user3 = $this->getUserByUsername('MaryJane');

        $magazine  = $this->getMagazineByName('acme', $user2);
        $magazine2 = $this->getMagazineByName('kuchnia', $user2);
        $magazine3 = $this->getMagazineByName('muzyka', $user2);

        $this->getEntryByTitle('treść 2', null, null, $magazine, $user2);
        $this->getEntryByTitle('treść 3', null, null, $magazine2, $user3);
        $this->getEntryByTitle('treść 4', null, null, $magazine3, $user2);
        $this->getEntryByTitle('treść 4', null, null, $magazine, $user3);
        $this->getEntryByTitle('treść 5', null, null, $magazine3, $user);
        $this->getEntryByTitle('treść 1', null, null, $magazine, $user);

        $manager->subscribe($magazine, $user);

        $crawler = $client->request('GET', '/m/acme');

        $this->assertSelectorTextContains('.kbin-magazine-header .kbin-sub', '2');

        $client->submit(
            $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block ')->selectButton('')->form()
        );

        $crawler = $client->followRedirect();

        $this->assertStringContainsString('kbin-block--active', $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block')->attr('class'));
        $this->assertSelectorTextContains('.kbin-magazine-header .kbin-sub', '1');

        $client->submit(
            $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block ')->selectButton('')->form()
        );

        $crawler = $client->followRedirect();

        $this->assertStringNotContainsString(
            'kbin-block--active',
            $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block')->attr('class')
        );
        $this->assertSelectorTextContains('.kbin-magazine-header .kbin-sub', '1');
    }

    public function testXmlUserCanBlockAndUnblockMagazine(): void // @todo
    {
        $client = $this->createClient();
        $client->loginUser($user = $this->getUserByUsername('JohnDoe'));

        $user2 = $this->getUserByUsername('JaneDoe');

        $magazine = $this->getMagazineByName('acme', $user2);
        $entry    = $this->getEntryByTitle('treść 2', null, null, $magazine, $user2);

        $id = $entry->getId();
        $client->request('GET', "/m/acme/t/$id/-/");

        $crawler = $client->followRedirect();

        $client->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');

        $client->submit(
            $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block')->selectButton('')->form()
        );

        $this->assertStringContainsString('{"isBlocked":true}', $client->getResponse()->getContent());

        $client->setServerParameter('HTTP_X-Requested-With', 'none');

        $client->request('GET', "/m/acme/t/$id/-/");

        $crawler = $client->followRedirect();

        $client->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');

        $client->submit(
            $crawler->filter('.kbin-sidebar .kbin-magazine .kbin-magazine-block')->selectButton('')->form()
        );

        $this->assertStringContainsString('{"isBlocked":false}', $client->getResponse()->getContent());
    }
}
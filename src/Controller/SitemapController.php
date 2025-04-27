<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends BaseController
{
    public function __construct(
        ConfigRepository $config,
        Security $security,
        EntityManagerInterface $entityManager,
        private readonly EventRepository $eventRepository,
    )
    {
        parent::__construct($config, $security, $entityManager);
    }

    #[Route('/-/sitemap', name: 'app_sitemap', methods: ['GET'], priority: 1)]
    public function index(Request $request): Response
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->addUrl($request, $xml, '/',           null, 'hourly', 1);
        $this->addUrl($request, $xml, '/-/licenses', null, 'weekly', 0.1);
        $this->addUrl($request, $xml, '/-/calendar', null, 'daily',  0.3);
        $this->addEvents($request, $xml);
        $xml->endElement();

        $response = new Response($xml->flush(), 200, ['content-type' => 'text/xml; charset=utf-8']);
        $response->setExpires(new \DateTime('now +1 hour'));

        return $response;
    }

    protected function addEvents(Request $request, \XMLWriter $xml) {
        $repo   = $this->eventRepository;
        $events = $repo->findPublic();

        foreach ($events as $event) {
            $this->addUrl($request, $xml, '/'.$event->getSlug(), null, 'weekly', 0.6);

            foreach ($event->getSchedules() as $schedule) {
                if ($schedule->isPublic()) {
                    $this->addUrl($request, $xml, '/'.$event->getSlug().'/'.$schedule->getSlug(), $schedule->getUpdatedAt(), 'hourly', 1);
                }
            }
        }
    }

    protected function addUrl(Request $request, \XMLWriter $xml, $url, ?\DateTime $lastmod = null, ?string $changefreq = null, ?int $priority = null) {
        static $root = null;

        if ($root === null) {
            $root    = $request->getSchemeAndHttpHost();
        }

        $xml->startElement('url');
        $xml->writeElement('loc', $root.$url);

        if ($lastmod) {
            $xml->writeElement('lastmod', $lastmod->format('Y-m-d'));
        }

        if ($changefreq) {
            $xml->writeElement('changefreq', $changefreq);
        }

        if ($priority !== null) {
            $xml->writeElement('priority', sprintf('%.1F', $priority));
        }
        $xml->endElement();
    }
}

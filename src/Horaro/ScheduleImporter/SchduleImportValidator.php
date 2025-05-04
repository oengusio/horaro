<?php

namespace App\Horaro\ScheduleImporter;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Horaro\Library\ReadableTime;
use App\Repository\ScheduleRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class SchduleImportValidator
{
    protected array $result = [];
    private array $themes;

    public function __construct(
        private readonly ScheduleRepository $repo,
        ContainerBagInterface $params,
    )
    {
        $this->themes = $params->get('horaro.themes');
    }

    public function validateDescription($description): array
    {
        $this->result = ['_errors' => false];

        $description = trim($description);
        $this->setFilteredValue('description', $description);

        if (mb_strlen($description) > 16*1024) {
            $this->addError('description', 'The description cannot be longer than 16k characters.');
        }

        return $this->result;
    }

    public function validateName($name, Event $event, Schedule $ref = null): string
    {
        $name = trim($name);

        if (mb_strlen($name) === 0) {
            $this->addError('name', 'The name cannot be empty.');
        }

        return $name;
    }

    public function validateSlug($slug, Event $event, Schedule $ref = null, $throwUp = false): string
    {
        $slug = trim($slug);

        if (!preg_match('/^[a-z0-9-]{2,}$/', $slug)) {
            $this->addError('slug', 'You can only use lowercase letters, numbers and dashes for a slug.', $throwUp);
        }
        elseif (preg_match('/^-+$/', $slug)) {
            $this->addError('slug', 'The slug cannot be all dashes only.', $throwUp);
        }
        elseif (preg_match('/^-|-$/', $slug)) {
            $this->addError('slug', 'The slug cannot start or end with a dash.', $throwUp);
        }
        else {
            $existing = $this->repo->findOneBy(['event' => $event, 'slug' => $slug]);

            if ($existing && (!$ref || $existing->getId() !== $ref->getId())) {
                $this->addError('slug', 'This slug is already in use by another schedule in this event.', $throwUp);
            }
        }

        return $slug;
    }

    public function validateTimezone($timezone, Event $event, Schedule $ref = null, $throwUp = false) {
        $timezone  = trim($timezone);
        $timezones = \DateTimeZone::listIdentifiers();

        if (!in_array($timezone, $timezones, true)) {
            $this->addError('timezone', 'Your selected timezone is invalid.', $throwUp);

            return 'UTC';
        }

        return $timezone;
    }

    public function validateStart($date, $time, Event $event, Schedule $ref = null, $throwUp = false): \DateTime|false|null
    {
        $this->setFilteredValue('start_date', $date);
        $this->setFilteredValue('start_time', $time);

        $okay = true;

        if (strlen(trim($date)) === 0) {
            $this->addError('start', 'No start date given.', $throwUp);
            $okay = false;
        }
        else {
            $d = \DateTime::createFromFormat('Y-m-d', $date);

            if (!$d) {
                $this->addError('start', 'The given start date is malformed.', $throwUp);
                $okay = false;
            }
            else {
                $year = $d->format('Y');
                $now  = date('Y');

                if ($year < 2000 || $year > $now+2) {
                    $this->addError('start', 'The given start date is out of range.', $throwUp);
                    $okay = false;
                }
            }
        }

        if (strlen(trim($time)) === 0) {
            $this->addError('start', 'No start time given.', $throwUp);
            $okay = false;
        }
        else {
            $t = \DateTime::createFromFormat('G:i', $time);

            if (!$t) {
                $this->addError('start', 'The given start time is malformed.', $throwUp);
                $okay = false;
            }
        }

        return $okay ? \DateTime::createFromFormat('Y-m-d G:i', "$date $time") : null;
    }

    public function validateWebsite($website, Event $event, Schedule $ref = null, $throwUp = false): ?string
    {
        $website = trim($website);

        if (mb_strlen($website) > 0) {
            $parts = parse_url($website);

            if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
                $this->addError('website', 'The website must use either HTTP or HTTPS.', $throwUp);
            }
        }

        return $website === '' ? null : $website;
    }

    public function validateTwitterAccount($account, Event $event, Schedule $ref = null, $throwUp = false): ?string
    {
        $account = trim($account);

        if (mb_strlen($account) > 0) {
            if (!preg_match('/^@?([a-zA-Z0-9-_]+)$/', $account, $match)) {
                $this->addError('twitter', 'The Twitter account name contains invalid characters.', $throwUp);
            }
            else {
                $account = $match[1];
            }
        }

        return $account === '' ? null : $account;
    }

    public function validateTwitchAccount($account, Event $event, Schedule $ref = null, $throwUp = false): ?string
    {
        $account = trim($account);

        if (mb_strlen($account) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $account)) {
            $this->addError('twitch', 'The Twitch account name contains invalid characters.', $throwUp);
        }

        return $account === '' ? null : $account;
    }

    public function validateTheme($theme, Event $event, Schedule $ref = null, $throwUp = false) {
        $theme = trim($theme);

        if (!in_array($theme, $this->themes, true)) {
            $this->addError('theme', 'Your selected theme is invalid.', $throwUp);

            return $ref ? $ref->getTheme() : $event->getTheme();
        }

        return $theme;
    }

    public function validateSecret($secret, $throwUp = false): ?string
    {
        $secret = trim($secret);

        if (mb_strlen($secret) > 20) {
            $this->addError('secret', 'The secret can only be up to 20 characters in length.', $throwUp);
        }

        if (mb_strlen($secret) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $secret)) {
            $this->addError('secret', 'The secret can only use the characters a-z, 0-9, dash and underscore.', $throwUp);
        }

        return $secret === '' ? null : $secret;
    }

    public function validateHiddenSecret($secret, $throwUp = false): ?string
    {
        $secret = trim($secret);

        if (mb_strlen($secret) > 20) {
            $this->addError('hidden_secret', 'The hidden column secret can only be up to 20 characters in length.', $throwUp);
        }

        if (mb_strlen($secret) > 0 && !preg_match('/^[a-zA-Z0-9_-]+$/', $secret)) {
            $this->addError('hidden_secret', 'The hidden column secret can only use the characters a-z, 0-9, dash and underscore.', $throwUp);
        }

        return $secret === '' ? null : $secret;
    }

    public function validateSetupTime($time, Event $event, Schedule $ref = null, $throwUp = false): \DateTime|false|string|null
    {
        $parser = new ReadableTime();
        $time   = trim($time);

        try {
            return $parser->parse($time);
        }
        catch (\InvalidArgumentException $e) {
            $this->addError('setup_time', 'Could not understand this time format.', $throwUp);
            return $time;
        }
    }

    // From old system

    protected function addError($field, $message, $throwUp = false): array
    {
        $this->result['_errors'] = true;
        $this->result[$field]['errors'] = true;
        $this->result[$field]['messages'][] = $message;

        if ($throwUp) {
            throw new \RuntimeException($message);
        }

        return $this->result;
    }

    protected function setFilteredValue($field, $value): void
    {
        $this->result[$field]['filtered'] = $value;

        if (!isset($this->result[$field]['errors'])) {
            $this->result[$field]['errors'] = false;
            $this->result[$field]['messages'] = [];
        }
    }
}

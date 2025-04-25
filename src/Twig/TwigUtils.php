<?php

namespace App\Twig;

use App\Entity\User;
use App\Horaro\Library\ReadableTime;
use App\Repository\ConfigRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TwigUtils {
    protected $versions = [];
    protected $app;

    // TODO: cache values
    public function __construct(
        protected ConfigRepository $configRepository,
        protected readonly Security $security,
    ) {
    }

    public function asset($path) {
        return $path;
    }

    public function shorten(string $string, int $maxlen) {
        if (mb_strlen($string) <= $maxlen) {
            return $string;
        }

        return mb_substr($string, 0, $maxlen).'…';
    }

    public function getLicenseMarkup(string $path) {
        $file = HORARO_ROOT.'/'.$path;

        if (!file_exists($file)) {
            return '<p class="text-error">License file ('.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').' not found.</p>';
        }

        $content = file_get_contents($file);

        return '<pre>'.htmlspecialchars($content, ENT_QUOTES, 'UTF-8').'</pre>';
    }

    public function userIsAdmin(?User $user = null) {
        /*$user = $user ?: $this->app['user'];

        if (!$user) {
            return false;
        }

        return $this->app['rolemanager']->userIsAdmin($user);*/

        return false;
    }

    public function userIsOp(?User $user = null) {
        /*$user = $user ?: $this->app['user'];

        if (!$user) {
            return false;
        }

        return $this->app['rolemanager']->userIsOp($user);*/

        return false;
    }

    public function userHasRole($role, ?User $user = null) {
        /*$user = $user ?: $this->app['user'];

        if (!$user) {
            return false;
        }

        return $this->app['rolemanager']->userHasRole($role, $user);*/

        return false;
    }

    public function userHasAdministrativeAccess(mixed $resource, ?User $user = null) {
        /*$user = $user ?: $this->app['user'];

        if (!$user) {
            return false;
        }

        return $this->app['rolemanager']->hasAdministrativeAccess($user, $resource);*/

        return false;
    }

    // TODO: fix these methods
    public function formValue(?array $result = null, $key = '', $default = null) {
        return isset($result[$key]) ? $result[$key]['filtered'] : $default;
    }

    public function formClass(?array $result = null, $key = '') {
        return empty($result[$key]['errors']) ? '' : ' has-error';
    }

    public function roleIcon($role) {
        $classes = [
            'ROLE_OP'    => 'fa-android',
            'ROLE_ADMIN' => 'fa-user-md',
            'ROLE_USER'  => 'fa-user',
            'ROLE_GHOST' => 'fa-ban'
        ];
        $cls = isset($classes[$role]) ? $classes[$role] : 'fa-question';

        return sprintf('<i class="fa %s"></i>', $cls);
    }

    public function roleClass($role) {
        $classes = [
            'ROLE_OP'    => 'danger',
            'ROLE_ADMIN' => 'warning',
            'ROLE_USER'  => 'primary',
            'ROLE_GHOST' => 'default'
        ];

        return isset($classes[$role]) ? $classes[$role] : 'primary';
    }

    public function roleName($role) {
        $names = [
            'ROLE_OP'    => 'Operator',
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_USER'  => 'Regular User',
            'ROLE_GHOST' => 'Ghost'
        ];

        return isset($names[$role]) ? $names[$role] : $role;
    }

    public function roleBadge($role) {
        $key = strtolower(str_replace('ROLE_', '', $role));

        return sprintf(
            '<span class="label h-role h-role-%s label-%s">%s %s</span>',
            $key, $this->roleClass($role), $this->roleIcon($role), $this->roleName($role)
        );
    }

    public function readableTime(?\DateTime $time = null) {
        if (!$time) return '';

        $parser = new ReadableTime();

        return $parser->stringify($time);
    }

    public function markdown($text) {
        return $text;// $this->app['markdown-converter']->convertInline($text);
    }

    public function appVersion(): string {
        $filename = HORARO_ROOT.'/version';

        return file_exists($filename) ? trim(file_get_contents($filename)) : 'version N/A';
    }

    public function csrfParamName(): string {
        return $this->configRepository->getByKey('csrf_token_name', '_csrf_token')->getValue();
    }

    private function getCurrentUser(): ?User
    {
        $curUser = $this->security->getUser();

        if (!($curUser instanceof User)) {
            throw new \RuntimeException('User is not a user???');
        }

        return $curUser;
    }
}

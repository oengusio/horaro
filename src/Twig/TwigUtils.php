<?php

namespace App\Twig;

use App\Entity\User;
use App\Horaro\Library\ReadableTime;
use App\Horaro\RoleManager;
use App\Horaro\Service\MarkdownService;
use App\Repository\ConfigRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormView;

class TwigUtils {
    protected $versions = [];

    // TODO: cache values
    public function __construct(
        protected ConfigRepository $configRepository,
        protected readonly Security $security,
        private readonly RoleManager $roleManager,
        private readonly MarkdownService $markdownService,
    ) {
    }

    public function asset($path) {
        return $path;
    }

    public function shorten(?string $string, int $maxlen): string
    {
        if (is_null($string)) {
            return '';
        }

        if (mb_strlen($string) <= $maxlen) {
            return $string;
        }

        return mb_substr($string, 0, $maxlen).'…';
    }

    public function getLicenseMarkup(string $path): string
    {
        $file = HORARO_ROOT.'/'.$path;

        if (!file_exists($file)) {
            return '<p class="text-error">License file ('.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').') not found.</p>';
        }

        $content = file_get_contents($file);

        return '<pre>'.htmlspecialchars($content, ENT_QUOTES, 'UTF-8').'</pre>';
    }

    public function userIsAdmin(?User $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->roleManager->userIsAdmin($user);
    }

    public function userIsOp(?User $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->roleManager->userIsOp($user);
    }

    public function userHasRole($role, ?User $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->roleManager->userHasRole($role, $user);
    }

    public function userHasAdministrativeAccess(mixed $resource, ?User $user = null): bool
    {
        $user = $user ?: $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $this->roleManager->hasAdministrativeAccess($user, $resource);
    }

    // TODO: fix these methods
    public function formValue(mixed $result = null, $key = '', $default = null): ?string {
        return 'FORM VALUE UTIL USED, PLEASE UPGRADE FORM';
//        return $result[$key]['filtered'] ?? $default;
    }

    public function formClass(?FormView $result = null, $key = ''): string
    {
        return $result[$key]->vars['valid'] ?? true ? '' : ' has-error';
    }

    public function roleIcon($role): string
    {
        $classes = [
            'ROLE_OP'    => 'fa-android',
            'ROLE_ADMIN' => 'fa-user-md',
            'ROLE_USER'  => 'fa-user',
            'ROLE_GHOST' => 'fa-ban'
        ];
        $cls = $classes[$role] ?? 'fa-question';

        return sprintf('<i class="fa %s"></i>', $cls);
    }

    public function roleClass($role): string
    {
        $classes = [
            'ROLE_OP'    => 'danger',
            'ROLE_ADMIN' => 'warning',
            'ROLE_USER'  => 'primary',
            'ROLE_GHOST' => 'default'
        ];

        return $classes[$role] ?? 'primary';
    }

    public function roleName($role): string
    {
        $names = [
            'ROLE_OP'    => 'Operator',
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_USER'  => 'Regular User',
            'ROLE_GHOST' => 'Ghost'
        ];

        return $names[$role] ?? $role;
    }

    public function roleBadge($role): string
    {
        $key = strtolower(str_replace('ROLE_', '', $role));

        return sprintf(
            '<span class="label h-role h-role-%s label-%s">%s %s</span>',
            $key, $this->roleClass($role), $this->roleIcon($role), $this->roleName($role)
        );
    }

    // TODO: make full util class
    public function readableTime(?\DateTime $time = null): string
    {
        if (!$time) return '';

        $parser = new ReadableTime();

        return $parser->stringify($time);
    }

    public function markdown($text): string
    {
        return $this->markdownService->convertInline($text);
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

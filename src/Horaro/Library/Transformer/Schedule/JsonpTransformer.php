<?php

namespace App\Horaro\Library\Transformer\Schedule;

use App\Entity\Schedule;
use App\Horaro\Library\Transformer\Schedule\JsonTransformer;

class JsonpTransformer extends JsonTransformer
{

    public function getContentType(): string
    {
        return 'application/javascript; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'js';
    }

    public function transform(Schedule $schedule, bool $public = false, bool $withHiddenColumns = false): mixed
    {
        $callback = $this->requestStack->getCurrentRequest()->query->get('callback');

        if (!$this->isValidCallback($callback)) {
            throw new \InvalidArgumentException('The given callback is malformed.');
        }

        $json = parent::transform($schedule, $public, $withHiddenColumns);

        // add empty inline comment to prevent content type sniffing attacks like Rosetta Flash
        return sprintf('/**/%s(%s);', $callback, $json);
    }

    /**
     * @see https://gist.github.com/ptz0n/1217080
     */
    protected function isValidCallback(string $callback): bool {
        $reserved = [
            'break', 'case', 'catch', 'class', 'const', 'continue', 'debugger', 'default', 'delete',
            'do', 'else', 'enum', 'export', 'extends', 'false', 'finally', 'for', 'function', 'if',
            'implements', 'import', 'in', 'instanceof', 'interface', 'let', 'new', 'null', 'package',
            'private', 'protected', 'public', 'return', 'static', 'super', 'switch', 'this', 'throw',
            'true', 'try', 'typeof', 'var', 'void', 'while', 'with', 'yield',
        ];

        foreach (explode('.', $callback) as $identifier) {
            if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*(?:\[(?:".+"|\'.+\'|\d+)\])*?$/', $identifier)) {
                return false;
            }

            if (in_array($identifier, $reserved, true)) {
                return false;
            }
        }

        return true;
    }
}

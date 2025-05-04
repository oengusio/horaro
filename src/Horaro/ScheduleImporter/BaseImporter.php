<?php

namespace App\Horaro\ScheduleImporter;

use App\Entity\Schedule;
use Doctrine\ORM\EntityManagerInterface;

abstract class BaseImporter {
    protected EntityManagerInterface $em;
    protected array $log;
    protected ScheduleValidator $validator;

    public function __construct(EntityManagerInterface $em, ScheduleValidator $validator) {
        $this->em        = $em;
        $this->validator = $validator;
        $this->log       = [];
    }

    abstract public function import(string $filePath, Schedule $schedule, bool $ignoreErrors, bool $updateMetadata): array;

    protected function persist($o): void
    {
        $this->em->persist($o);
    }

    protected function remove($o): void
    {
        $this->em->remove($o);
    }

    protected function flush(): void
    {
        $this->em->flush();
    }

    protected function log($type, $msg): void
    {
        $this->log[] = [$type, $msg];
    }

    protected function returnLog(): array
    {
        $l = $this->log;
        $this->log = [];

        return $l;
    }

    protected function replaceColumns(Schedule $schedule, array $columns): array
    {
        foreach ($schedule->getColumns() as $col) {
            $this->remove($col);
        }

        foreach ($columns as $col) {
            $col->setSchedule($schedule);
            $this->persist($col);
        }

        $this->flush();

        $columnIDs = [];

        foreach ($columns as $col) {
            $columnIDs[] = $col->getId();
        }

        return $columnIDs;
    }

    protected function replaceItems(Schedule $schedule, array $items, array $columnIDs): void
    {
        foreach ($schedule->getItems() as $item) {
            $this->remove($item);
        }

        foreach ($items as $item) {
            $extra = [];

            foreach ($item->tmpExtra as $idx => $value) {
                $columnID = $columnIDs[$idx];
                $extra[$columnID] = $value;
            }

            $item->setSchedule($schedule)->setExtra($extra);
            $this->persist($item);
        }
    }
}

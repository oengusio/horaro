<?php

namespace App\Horaro\Library;

use App\Entity\Schedule;
use App\Entity\ScheduleItem;
use Doctrine\Common\Collections\Collection;

class ScheduleItemIterator implements \Iterator
{
    protected Schedule $schedule;
    protected Collection $items;
    protected \DateTime $time;
    protected \DateInterval $setup;
    protected mixed $optionsCol;
    protected ?ScheduleItem $current;
    protected int $position;

    public function __construct(Schedule $schedule) {
        $this->schedule   = $schedule;
        $this->items      = $schedule->getItems();
        $this->setup      = $schedule->getSetupTimeDateInterval();
        $this->optionsCol = $schedule->getOptionsColumn();

        $this->rewind();
    }

    public function rewind(): void {
        $this->time     = $this->schedule->getLocalStart();
        $this->position = 0;

        $this->updateCurrentItem();
    }

    public function current(): ?ScheduleItem {
        return $this->current;
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        $this->position++;

        $setupTime = $this->setup;

        if ($this->optionsCol) {
            $customSetup = $this->current->getSetupTime($this->optionsCol);

            if ($customSetup) {
                $setupTime = $customSetup;
            }
        }

        $this->time->add($this->current->getDateInterval());
        $this->time->add($setupTime);

        $this->updateCurrentItem();
    }

    public function valid(): bool {
        return isset($this->items[$this->position]);
    }

    protected function updateCurrentItem(): void {
        $item = null;

        if ($this->valid()) {
            $item = $this->items[$this->position];
            $item->setScheduled($this->time);
        }

        $this->current = $item;
    }
}

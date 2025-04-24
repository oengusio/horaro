<?php

namespace App\Horaro\Library;

use App\Entity\Schedule;
use Doctrine\Common\Collections\Collection;

class ScheduleItemIterator implements \Iterator
{
    protected Schedule $schedule;
    protected Collection $items;
    protected \DateTime $time;
    protected \DateInterval $setup;
    protected mixed $optionsCol;
    protected $current;
    protected $position;

    public function __construct(Schedule $schedule) {
        $this->schedule   = $schedule;
        $this->items      = $schedule->getItems();
        $this->setup      = $schedule->getSetupTimeDateInterval();
        $this->optionsCol = $schedule->getOptionsColumn();

        $this->rewind();
    }

    #[\ReturnTypeWillChange]
    public function rewind() {
        $this->time     = $this->schedule->getLocalStart();
        $this->position = 0;

        $this->updateCurrentItem();
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return $this->current;
    }

    #[\ReturnTypeWillChange]
    public function key() {
        return $this->position;
    }

    #[\ReturnTypeWillChange]
    public function next() {
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

    #[\ReturnTypeWillChange]
    public function valid() {
        return isset($this->items[$this->position]);
    }

    protected function updateCurrentItem() {
        $item = null;

        if ($this->valid()) {
            $item = $this->items[$this->position];
            $item->setScheduled($this->time);
        }

        $this->current = $item;
    }
}

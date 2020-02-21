<?php

namespace skywars\provider;

use skywars\provider\target\TargetOffline;

interface Provider {

    public function getTargetOffline(string $name): ?TargetOffline;

    public function setTargetOffline(TargetOffline $target);

    public function getName(): string;
}
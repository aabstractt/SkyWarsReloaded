<?php


namespace skywars\provider\target;


class TargetOffline {

    public $data;

    /**
     * TargetOffline constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['username'];
    }
}
<?php
namespace luoyy\Blade\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \luoyy\Blade\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}

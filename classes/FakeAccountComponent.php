<?php namespace Tlokuus\LoginWithSocial\Classes;

use RainLab\User\Components\Account as AccountComponent;

class FakeAccountComponent extends AccountComponent {

    public function public_sendActivationEmail($user) {
        $this->sendActivationEmail($user);
    }

    public function public_isRegisterThrottled() {
        return $this->isRegisterThrottled();
    }
}
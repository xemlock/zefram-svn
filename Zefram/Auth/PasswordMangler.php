<?php

abstract class Zefram_Auth_PasswordMangler
{
    abstract public function mangle($password);
    abstract public function validate($password, $challenge, $context = null);
}

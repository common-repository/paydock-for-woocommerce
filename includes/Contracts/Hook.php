<?php

namespace Paydock\Contracts;

interface Hook {
	public static function handle(): void;
}

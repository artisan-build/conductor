<?php

it('runs', function () {
    $this->artisan('run')->assertExitCode(0);
});

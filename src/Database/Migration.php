<?php

declare(strict_types=1);

namespace CentralDeAulas\Database;

interface Migration
{
    public function name(): string;

    public function up(PdoDatabase $database): void;
}

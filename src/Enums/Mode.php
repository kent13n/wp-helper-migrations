<?php

namespace WpHelperMigrations\Enums;

enum Mode
{
    case Insert;
    case Update;
    case Delete;
    case Unknown;
}
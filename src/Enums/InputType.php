<?php

namespace MazerDev\Dialogify\Enums;

enum InputType: string
{
    case Text = 'text';
    case Number = 'number';
    case Password = 'password';
    case Email = 'email';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'datetime';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
}

<?php

namespace IGE\ChannelLister\Enums;

enum InputType: string
{
    case ALERT = 'alert';
    case CHECKBOX = 'checkbox';
    case COMMA_SEPARATED = 'comma-separated';
    case CURRENCY = 'currency';
    case CUSTOM = 'custom';
    case DECIMAL = 'decimal';
    case INTEGER = 'integer';
    case SELECT = 'select';
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case URL = 'url';
};

<?php

// namespace IGE\ChannelLister\Http\Controllers;

namespace IGE\ChannelLister\Enums;

enum InputType: string
{
    case ALERT = 'alert';
    case CHECKBOX = 'checkbox';
    case CLONESITE_CATS = 'clonesite-cats';
    case CLONESITE_TAGS = 'clonesite-tags';
    case CURRENCY = 'currency';
    case CUSTOM = 'custom';
    case DECIMAL = 'decimal';
    case INTEGER = 'integer';
    case SELECT = 'select';
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case URL = 'url';

};

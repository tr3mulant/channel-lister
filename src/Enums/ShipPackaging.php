<?php

namespace IGE\ChannelLister\Enums;

enum ShipPackaging: string
{
    case POLYMAILER = 'Polymailer';
    case EXPRESSENVELOPE = 'Expressenvelope';
    case BOX = 'Box';
    case OTHER = 'Other';
};

<?php

namespace IGE\ChannelLister\Enums;

enum Flag: string
{
    case READY_TO_BE_LISTED = 'Ready to be listed';
    case NEW = 'New';
    case NEEDS_PHOTOS = 'Needs Photos';
    case CREATED = 'Created';
    case NEEDS_REVISION = 'Needs Revision';
};

<?php

namespace App\Enums;

enum IntakeMethod: string
{
    case Download = 'download';
    case UploadForReview = 'upload_for_review';
}

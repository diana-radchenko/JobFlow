<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Applied = 'applied';
    case InterviewScheduled = 'interview_scheduled';
    case Rejected = 'rejected';
    case Offer = 'offer';
    case Hired = 'hired';
}

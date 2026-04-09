<?php

namespace App\Enums;

enum EducationDegree: string
{
    case HighSchool = 'high_school';
    case Certificate = 'certificate';
    case Associate = 'associate';
    case Bachelors = 'bachelors';
    case Masters = 'masters';
    case Doctorate = 'doctorate';
    case PostdoctoralResearcher = 'postdoctoral_researcher';
}

<?php

namespace App\Enums;

enum WritingReportType: string
{
    case GRAMMAR = 'grammar';
    case SUMMARY = 'summary';
    case FACTCHECK = 'factcheck';
    case FEEDBACK = 'feedback';
}

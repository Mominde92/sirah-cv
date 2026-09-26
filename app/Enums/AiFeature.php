<?php

declare(strict_types=1);

namespace App\Enums;

enum AiFeature: string
{
    case GenerateSummary = 'generate_summary';
    case ImproveExperience = 'improve_experience';
    case RewriteBullets = 'rewrite_bullets';
    case SuggestSkills = 'suggest_skills';
    case ImproveGrammar = 'improve_grammar';
    case AtsKeywords = 'ats_keywords';
    case AnalyzeResume = 'analyze_resume';
    case AnalyzeJob = 'analyze_job';
    case MatchJob = 'match_job';
    case ParseResume = 'parse_resume';
    case AnalyzeDesign = 'analyze_design';
}

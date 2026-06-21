<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamAttemptScoringTest extends TestCase
{
    #[Test]
    public function institution_attempt_percentage_is_computed_from_score_and_total_points(): void
    {
        $attempt = new InstitutionAttempt([
            'score' => 60,
            'total_points' => 100,
        ]);

        $this->assertSame(60.0, $attempt->percentage);
    }

    #[Test]
    public function institution_attempt_passes_when_raw_score_meets_mpl(): void
    {
        $assessment = new InstitutionAssessment([
            'passing_score' => 60,
        ]);

        $attempt = new InstitutionAttempt([
            'score' => 60,
            'total_points' => 100,
        ]);
        $attempt->setRelation('assessment', $assessment);

        $this->assertTrue($attempt->isPassed());
    }

    #[Test]
    public function institution_attempt_fails_when_percentage_is_high_but_raw_score_is_below_mpl(): void
    {
        $assessment = new InstitutionAssessment([
            'passing_score' => 60,
        ]);

        $attempt = new InstitutionAttempt([
            'score' => 59,
            'total_points' => 60,
        ]);
        $attempt->setRelation('assessment', $assessment);

        $this->assertGreaterThan(98, $attempt->percentage);
        $this->assertFalse($attempt->isPassed());
    }

    #[Test]
    public function national_attempt_percentage_is_computed_from_score_and_total_points(): void
    {
        $attempt = new NationalAttempt([
            'score' => 45,
            'total_points' => 50,
        ]);

        $this->assertSame(90.0, $attempt->percentage);
    }

    #[Test]
    public function national_attempt_passes_when_raw_score_meets_mpl(): void
    {
        $assessment = new NationalAssessment([
            'passing_score' => 45,
        ]);

        $attempt = new NationalAttempt([
            'score' => 45,
            'total_points' => 50,
        ]);
        $attempt->setRelation('assessment', $assessment);

        $this->assertTrue($attempt->isPassed());
    }
}

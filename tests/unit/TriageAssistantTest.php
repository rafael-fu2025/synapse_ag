<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\TriageAssistant;

class TriageAssistantTest extends CIUnitTestCase
{
    private TriageAssistant $triageAssistant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->triageAssistant = new TriageAssistant();
    }

    public function testUrgentKeywordsTriggerUrgentPriority()
    {
        $result = $this->triageAssistant->analyze('Patient is experiencing chest pain');
        $this->assertSame('urgent', $result['predicted_priority']);
        $this->assertGreaterThanOrEqual(0.90, $result['confidence_score']);
    }

    public function testHighKeywordsTriggerHighPriority()
    {
        $result = $this->triageAssistant->analyze('Patient has asthma');
        $this->assertSame('high', $result['predicted_priority']);
    }

    public function testMediumKeywordsTriggerMediumPriority()
    {
        $result = $this->triageAssistant->analyze('Patient has cough and cold');
        $this->assertSame('medium', $result['predicted_priority']);
    }

    public function testLowKeywordsTriggerLowPriority()
    {
        $result = $this->triageAssistant->analyze('Requesting general check-up');
        $this->assertSame('low', $result['predicted_priority']);
    }

    public function testVitalsEscalation()
    {
        // Dizzy keyword triggers high. But extreme vitals (like heart rate 130) should escalate it to urgent.
        $result = $this->triageAssistant->analyze('Patient is dizzy', [
            'temperature' => 37.0,
            'heart_rate' => 130,
            'systolic_bp' => 120
        ]);
        $this->assertSame('urgent', $result['predicted_priority']);
        $this->assertTrue($result['features_used']['vitals_triggered']);
    }

    public function testAllergyEscalation()
    {
        // Patient complaint mentions aspirin, and they have severe allergy to aspirin.
        // This should force priority to urgent.
        $allergies = [
            [
                'allergen' => 'Aspirin',
                'severity' => 'severe'
            ]
        ];
        $result = $this->triageAssistant->analyze('Took aspirin, feeling sick', null, $allergies);
        $this->assertSame('urgent', $result['predicted_priority']);
        $this->assertTrue($result['features_used']['allergy_triggered']);
    }
}

<?php
require 'vendor/autoload.php';
$db = \Config\Database::connect();

$triage = $db->table('ai_triage_predictions')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
$risk = $db->table('ai_risk_scores')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
$appt = $db->table('counselling_appointments')->where('no_show_probability IS NOT NULL')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();

echo "Triage ID: " . ($triage ? $triage['consultation_id'] : 'none') . "\n";
echo "Risk Response ID: " . ($risk ? $risk['assessment_response_id'] : 'none') . "\n";
echo "Appt ID: " . ($appt ? $appt['id'] : 'none') . "\n";

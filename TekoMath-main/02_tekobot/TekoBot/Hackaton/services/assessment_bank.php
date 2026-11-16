<?php
// Server-only keys shared by chat assessments and diagnostic evaluations.
class AssessmentBank {
    public static function all(): array {
        static $questions;
        if ($questions === null) {
            $data = json_decode(file_get_contents(__DIR__.'/../config/assessment_bank.json'), true, 512, JSON_THROW_ON_ERROR);
            $questions = $data['questions'];
        }
        return $questions;
    }

    public static function questions(string $unit, string $subtopic): array {
        return array_values(array_filter(self::all(), fn($q) => $q['unit'] === $unit && $q['subtopic'] === $subtopic));
    }

    public static function catalog(): array {
        $curriculum = json_decode(file_get_contents(__DIR__.'/../database/curriculum.json'), true, 512, JSON_THROW_ON_ERROR);
        return array_map(fn($u) => ['key'=>$u['key'], 'title'=>$u['title'], 'subtopics'=>array_map(
            fn($s) => ['id'=>$s['id'], 'title'=>$s['title']], $u['subtopics'])], $curriculum['units']);
    }
}

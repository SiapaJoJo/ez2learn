<?php
/**
 * Progress Tracking Configuration
 * 
 * MODIFIABILITY: Change weights here to adjust progress calculation.
 * All changes take effect immediately across the system.
 * Edit time: < 1 hour for weight adjustments.
 */

// Progress component weights (must sum to 1.0)
define('PROGRESS_WEIGHT_MATERIALS', 0.34);    // 34% weight for learning materials
define('PROGRESS_WEIGHT_ASSIGNMENTS', 0.33);  // 33% weight for assignments
define('PROGRESS_WEIGHT_QUIZZES', 0.33);      // 33% weight for quizzes

// Certificate eligibility threshold
define('PROGRESS_CERTIFICATE_THRESHOLD', 100); // Must reach 100% completion

// Recoverability settings
define('PROGRESS_RETRY_ATTEMPTS', 3);
define('PROGRESS_RETRY_DELAY_MS', 2000); // 2 seconds between retries

// UI settings
define('PROGRESS_TOAST_DURATION', 3000); // 3 seconds
define('PROGRESS_ANIMATION_DURATION', 800); // 0.8 seconds

/**
 * Get progress weights as array
 * If a category has 0 items, redistributes its weight proportionally
 * 
 * @param int $total_materials
 * @param int $total_assignments
 * @param int $total_quizzes
 * @return array ['materials' => float, 'assignments' => float, 'quizzes' => float]
 */
function get_adjusted_weights($total_materials, $total_assignments, $total_quizzes) {
    $weights = [
        'materials' => PROGRESS_WEIGHT_MATERIALS,
        'assignments' => PROGRESS_WEIGHT_ASSIGNMENTS,
        'quizzes' => PROGRESS_WEIGHT_QUIZZES
    ];
    
    $totals = [
        'materials' => $total_materials,
        'assignments' => $total_assignments,
        'quizzes' => $total_quizzes
    ];
    
    // Find categories with zero items
    $zero_categories = [];
    $active_categories = [];
    $weight_to_redistribute = 0;
    
    foreach ($totals as $category => $total) {
        if ($total == 0) {
            $zero_categories[] = $category;
            $weight_to_redistribute += $weights[$category];
        } else {
            $active_categories[] = $category;
        }
    }
    
    // If all categories are zero, return zeros
    if (count($active_categories) == 0) {
        return ['materials' => 0, 'assignments' => 0, 'quizzes' => 0];
    }
    
    // Redistribute weight from zero categories
    if ($weight_to_redistribute > 0) {
        $weight_per_active = $weight_to_redistribute / count($active_categories);
        foreach ($zero_categories as $category) {
            $weights[$category] = 0;
        }
        foreach ($active_categories as $category) {
            $weights[$category] += $weight_per_active;
        }
    }
    
    return $weights;
}
?>

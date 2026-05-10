<?php
$chart_labels = [];
$chart_pass_sem = [];
$chart_pass_total = [];
$chart_fail_sem = [];
$chart_fail_total = [];
$chart_sem_gpa = [];
$chart_cum_gpa = [];

if (isset($subjects_by_semester) && !empty($subjects_by_semester)) {
    $total_pass = 0; $total_fail = 0;
    $cum_weighted_score = 0; $cum_credits_for_gpa = 0;

    $sorted_data = $subjects_by_semester;
    ksort($sorted_data);

    foreach ($sorted_data as $sem_num => $list) {
        $sem_pass = 0; $sem_fail = 0;
        $sem_weighted_score = 0; $sem_gpa_credits = 0;

        foreach ($list as $sub) {
            $p = $sub['score_process']; $m = $sub['score_midterm'];
            $f = $sub['score_final']; $f2 = $sub['score_retake'];
            
            if ($f !== null || $f2 !== null) {
                $max_f = max($f ?? 0, $f2 ?? 0);
                $total_num = round(($p * 0.1) + ($m * 0.3) + ($max_f * 0.6), 1);
                
                $gpa4 = 0;
                if ($total_num >= 4.0) { 
                    $sem_pass += $sub['credit'];
                    if ($total_num >= 8.5) $gpa4 = 4.0;
                    else if ($total_num >= 8.0) $gpa4 = 3.5;
                    else if ($total_num >= 7.0) $gpa4 = 3.0;
                    else if ($total_num >= 6.5) $gpa4 = 2.5;
                    else if ($total_num >= 5.5) $gpa4 = 2.0;
                    else if ($total_num >= 5.0) $gpa4 = 1.5;
                    else $gpa4 = 1.0;
                } else {
                    $sem_fail += $sub['credit'];
                }
                $sem_weighted_score += ($gpa4 * $sub['credit']);
                $sem_gpa_credits += $sub['credit'];
            }
        }
        $total_pass += $sem_pass; $total_fail += $sem_fail;
        $cum_weighted_score += $sem_weighted_score;
        $cum_credits_for_gpa += $sem_gpa_credits;

        $chart_labels[] = "Kỳ " . $sem_num;
        $chart_pass_sem[] = $sem_pass;
        $chart_pass_total[] = $total_pass;
        $chart_fail_sem[] = $sem_fail;
        $chart_fail_total[] = $total_fail;
        $chart_sem_gpa[] = ($sem_gpa_credits > 0) ? round($sem_weighted_score / $sem_gpa_credits, 2) : 0;
        $chart_cum_gpa[] = ($cum_credits_for_gpa > 0) ? round($cum_weighted_score / $cum_credits_for_gpa, 2) : 0;
    }
}
?>
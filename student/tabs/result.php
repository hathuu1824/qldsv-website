<?php
$sql_progress = "SELECT * FROM class_results WHERE class_id = ? AND account_id = ?";
$stmt_progress = $conn->prepare($sql_progress);
$stmt_progress->bind_param("ii", $class_id, $user_id);
$stmt_progress->execute();
$data = $stmt_progress->get_result()->fetch_assoc();

$res = [
    'p' => '-', 'm' => '-', 'f1' => '-', 'f2' => '-', 'final' => '-',
    't10' => '-', 't4' => '-', 'letter' => '-', 'status' => '-', 'class' => 'grade-none'
];

if ($data) {
    $p  = $data['score_process'];   
    $m  = $data['score_midterm'];   
    $f1 = $data['score_final'];  
    $f2 = $data['score_retake'];  

    $res['p']  = ($p !== null) ? number_format($p, 1) : '-';
    $res['m']  = ($m !== null) ? number_format($m, 1) : '-';
    $res['f1'] = ($f1 !== null) ? number_format($f1, 1) : '-';
    $res['f2'] = ($f2 !== null) ? number_format($f2, 1) : '-';

    $final_exam = null;
    if ($f1 !== null || $f2 !== null) {
        $final_exam = max($f1 ?? 0, $f2 ?? 0);
        $res['final'] = number_format($final_exam, 1);
    }
    if ($p !== null) {
        $res['status'] = ($p >= 5) ? '<span style="color: #2ecc71;">Đủ ĐKDT</span>' : '<span style="color: #e74c3c;">Không đủ ĐKDT</span>';
    }
    if ($p !== null && $m !== null && $final_exam !== null) {
        $total = ($p * 0.1) + ($m * 0.3) + ($final_exam * 0.6);
        $res['t10'] = number_format($total, 1);

        if ($total >= 8.5) {
            $res['t4'] = '4.0'; $res['letter'] = 'A';  $res['class'] = 'grade-a';
        } elseif ($total >= 8.0) {
            $res['t4'] = '3.5'; $res['letter'] = 'B+'; $res['class'] = 'grade-b';
        } elseif ($total >= 7.0) {
            $res['t4'] = '3.0'; $res['letter'] = 'B';  $res['class'] = 'grade-b';
        } elseif ($total >= 6.5) {
            $res['t4'] = '2.5'; $res['letter'] = 'C+'; $res['class'] = 'grade-c';
        } elseif ($total >= 5.5) {
            $res['t4'] = '2.0'; $res['letter'] = 'C';  $res['class'] = 'grade-c';
        } elseif ($total >= 5.0) {
            $res['t4'] = '1.5'; $res['letter'] = 'D+'; $res['class'] = 'grade-d';
        } elseif ($total >= 4.0) {
            $res['t4'] = '1.0'; $res['letter'] = 'D';  $res['class'] = 'grade-d';
        } else {
            $res['t4'] = '0.0'; $res['letter'] = 'F';  $res['class'] = 'grade-f';
        }
    }
}
?>

<div class="grade-container">
    <div class="grade-card">
        <section class="grade-group horizontal">
            <div class="grade-title-side">
                <h5>Điểm thành phần & ĐKDT</h5>
            </div>
            <div class="grade-table-side">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th>Chuyên cần (10%)</th>
                            <th>Kiểm tra (30%)</th>
                            <th>Điều kiện dự thi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $res['p'] ?></td>
                            <td><?= $res['m'] ?></td>
                            <td><?= $res['status'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grade-group horizontal">
            <div class="grade-title-side">
                <h5>Điểm thi kết thúc học phần</h5>
            </div>
            <div class="grade-table-side">
                <table class="grade-table">
                    <thead>
                        <tr>
                            <th>Thi lần 1</th>
                            <th>Thi lần 2</th>
                            <th>Cuối kỳ (60%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $res['f1'] ?></td>
                            <td><?= $res['f2'] ?></td>
                            <td><?= $res['final'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grade-group horizontal">
            <div class="grade-title-side">
                <h5>Điểm thành phần <br>& ĐKDT</h5>
            </div>
            <div class="grade-table-side">
                <table class="grade-table summary-table">
                    <thead>
                        <tr>
                            <th>Điểm hệ 10</th>
                            <th>Điểm hệ 4</th>
                            <th>Điểm chữ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: bold;"><?= $res['t10'] ?></td>
                            <td style="font-weight: bold;"><?= $res['t4'] ?></td>
                            <td class="grade-cell">
                                <span class="grade-badge <?= $res['class'] ?>"><?= $res['letter'] ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
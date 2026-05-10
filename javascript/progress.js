/**
 * Hàm hiển thị Popup và xử lý tính toán điểm
 * @param {Object} sub - Đối tượng chứa toàn bộ thông tin môn học từ PHP
 */
function showPopup(sub) {
    const modal = document.getElementById('subjectPopup');
    modal.style.display = "block";

    // 1. Gán thông tin cơ bản
    document.getElementById('pop-title').innerText = sub.subject_name || "N/A";
    document.getElementById('pop-code').innerText = sub.subject_code || "N/A";
    document.getElementById('pop-credit').innerText = sub.credit || "0";

    // 2. Xử lý ép kiểu dữ liệu - Chuyển sang số hoặc giữ null nếu trống
    const p = (sub.score_process !== null && sub.score_process !== "") ? parseFloat(sub.score_process) : null;
    const m = (sub.score_midterm !== null && sub.score_midterm !== "") ? parseFloat(sub.score_midterm) : null;
    const f = (sub.score_final !== null && sub.score_final !== "") ? parseFloat(sub.score_final) : null;
    const f2 = (sub.score_retake !== null && sub.score_retake !== "") ? parseFloat(sub.score_retake) : null;

    // 3. Hiển thị điểm thành phần lên bảng
    document.getElementById('score-process').innerText = p !== null ? p : "-";
    document.getElementById('score-midterm').innerText = m !== null ? m : "-";
    document.getElementById('score-fterm').innerText = f !== null ? f : "-";
    document.getElementById('score-retake').innerText = f2 !== null ? f2 : "-";

    // 4. LOGIC ĐIỀU KIỆN DỰ THI (Chỉ cần có điểm CC và KT là đủ điều kiện)
    let displayStatus = "-";
    if (p !== null && m !== null) {
        // Chỉ cần có điểm là Đủ điều kiện (vì 9.5 và 8 là quá cao rồi)
        displayStatus = "Đủ điều kiện";
    }
    document.getElementById('score-status').innerText = displayStatus;

    // 5. LOGIC TỔNG KẾT (CHỈ CHẠY KHI CÓ ĐIỂM THI)
    let displayWeightedFinal = "-";
    let displayTotal = "-";
    let displayGPA = "-";
    let displayLetter = "-";

    let score1 = (f !== null && !isNaN(f)) ? f : null;
    let score2 = (f2 !== null && !isNaN(f2)) ? f2 : null;

    if (score1 !== null || score2 !== null) {
        
        let maxFinal = Math.max(score1 || 0, score2 || 0);
        
        displayWeightedFinal = maxFinal.toFixed(1);

        let total = (p * 0.1) + (m * 0.3) + (maxFinal * 0.6);
        total = Math.round(total * 10) / 10; 
        displayTotal = total.toFixed(1);

        if (total >= 8.5) { displayGPA = "4.0"; displayLetter = "A"; }
        else if (total >= 8.0) { displayGPA = "3.5"; displayLetter = "B+"; }
        else if (total >= 7.0) { displayGPA = "3.0"; displayLetter = "B"; }
        else if (total >= 6.5) { displayGPA = "2.5"; displayLetter = "C+"; }
        else if (total >= 5.5) { displayGPA = "2.0"; displayLetter = "C"; }
        else if (total >= 5.0) { displayGPA = "1.5"; displayLetter = "D+"; }
        else if (total >= 4.0) { displayGPA = "1.0"; displayLetter = "D"; }
        else { displayGPA = "0.0"; displayLetter = "F"; }
    }

    // 6. Đổ dữ liệu ra bảng
    document.getElementById('score-final').innerText = displayWeightedFinal;
    document.getElementById('score-total').innerText = displayTotal;
    document.getElementById('score-gpa').innerText = displayGPA;
    document.getElementById('score-letter').innerText = displayLetter;

    const letterEl = document.getElementById('score-letter');
    letterEl.innerText = displayLetter;

    letterEl.className = ""; 
    let firstChar = displayLetter.charAt(0).toUpperCase();

    switch (firstChar) {
        case 'A':
            letterEl.classList.add("grade-a");
            break;
        case 'B':
            letterEl.classList.add("grade-b");
            break;
        case 'C':
            letterEl.classList.add("grade-c");
            break;
        case 'D':
            letterEl.classList.add("grade-d");
            break;
        case 'F':
            letterEl.classList.add("grade-f");
            break;
        default:
            letterEl.classList.add("grade-none");
            break;
    }
}

/**
 * Đóng Popup
 */
function closePopup() {
    document.getElementById('subjectPopup').style.display = "none";
}

/**
 * Đóng Popup khi click ra ngoài vùng Modal Content
 */
window.onclick = function(event) {
    const modal = document.getElementById('subjectPopup');
    if (event.target == modal) {
        closePopup();
    }
}
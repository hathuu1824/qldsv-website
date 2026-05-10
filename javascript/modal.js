/**
 * ============================================================
 * 1. MODAL CHÍNH: CHI TIẾT HỌC PHẦN (classDetailsModal)
 * Điều khiển: Thông tin chung, Đề cương, Link học trực tuyến...
 * ============================================================
 */

function openClassModal(targetTabId = 'tab-info') {
    const modal = document.getElementById('classDetailsModal');
    if (!modal) return;

    disableEditMode(); // Luôn mặc định ở chế độ xem
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    const tabButton = document.querySelector(`.tab-btn[onclick*="${targetTabId}"]`);
    if (tabButton) tabButton.click();
}

function closeClassModal() {
    const modal = document.getElementById('classDetailsModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function switchModalTab(evt, tabId) {
    const panes = document.querySelectorAll('.tab-pane-modal');
    panes.forEach(pane => {
        pane.style.display = 'none';
        pane.classList.remove('active');
    });

    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => btn.classList.remove('active'));

    const targetPane = document.getElementById(tabId);
    if (targetPane) {
        targetPane.style.display = 'block';
        targetPane.classList.add('active');
    }

    if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
}

/**
 * LOGIC XEM/SỬA (VIEW/EDIT MODE)
 */
function enableEditMode() {
    const form = document.getElementById('mainUpdateForm');
    if (form) {
        form.classList.remove('is-viewing');
        form.classList.add('is-editing');
        const container = document.getElementById('general-info-container');
        if (container) container.classList.add('editing');
        
        const firstInput = form.querySelector('input:not([readonly]), textarea');
        if (firstInput) firstInput.focus();
    }
}

function disableEditMode() {
    const form = document.getElementById('mainUpdateForm');
    if (form) {
        form.classList.remove('is-editing');
        form.classList.add('is-viewing');
        const container = document.getElementById('general-info-container');
        if (container) container.classList.remove('editing');
    }
}

/**
 * ============================================================
 * 2. MODAL PHỤ: CẬP NHẬT BUỔI HỌC (modalEditSession)
 * Điều khiển: Nội dung bài học, Học liệu (link/file)
 * ============================================================
 */

function openEditSessionModal(id, content = '', link = '') {
    const modal = document.getElementById('modalEditSession');
    if (!modal) return;

    document.getElementById('edit_session_id').value = id;
    document.getElementById('edit_content').value = content;
    document.getElementById('edit_document_link').value = link;

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeSessionModal() {
    const modal = document.getElementById('modalEditSession');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * ============================================================
 * 3. MODAL PHỤ: CHI TIẾT NGHỈ PHÉP (modalLeaveDetail)
 * Điều khiển: Xem danh sách sinh viên xin nghỉ trong ngày
 * ============================================================
 */

function viewLeaveRequests(date, class_id) {
    const body = document.getElementById('leave_list_body');
    const modal = document.getElementById('modalLeaveDetail');
    if (!modal) return;

    body.innerHTML = '<tr><td colspan="4" style="text-align:center;">Đang tải dữ liệu...</td></tr>';
    modal.style.display = 'block';
    document.getElementById('leave_modal_title').innerText = `Sinh viên nghỉ ngày ${date}`;

    fetch(`tabs/process/get_leave_list.php?date=${date}&class_id=${class_id}`)
        .then(response => response.text())
        .then(html => { body.innerHTML = html; })
        .catch(err => {
            body.innerHTML = '<tr><td colspan="4" style="color:red; text-align:center;">Lỗi tải dữ liệu.</td></tr>';
        });
}

function closeLeaveModal() {
    const modal = document.getElementById('modalLeaveDetail');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

/**
 * ============================================================
 * 4. LOGIC TÍNH NĂNG: ĐIỂM DANH SINH VIÊN
 * Điều khiển: Hiện danh sách sinh viên bên dưới bảng lịch dạy
 * ============================================================
 */
// 1. Khi bấm nút Điểm danh ở bảng trên
function loadStudentList(sessionId, sessionNumber, sessionDate, classId) {
    const section = document.getElementById('attendance-detail-section');
    
    // Đổ dữ liệu vào kho chứa
    document.getElementById('current-session-date').value = sessionId;
    document.getElementById('current-session-date').value = sessionDate;
    document.getElementById('current-class-id').value = classId;
    
    document.getElementById('attendance-title').innerText = "Điểm danh sinh viên - Buổi " + sessionNumber;

    // Hiện bảng và cuộn
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Gọi AJAX nạp danh sách SV (Lưu ý đường dẫn có tabs/ hay không tùy thuộc vào file chính của bạn)
    const listBody = document.getElementById('student-list-body');
    listBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Đang tải...</td></tr>';
    
    fetch(`tabs/process/get_students_attendance.php?session_id=${sessionId}`)
        .then(response => response.text())
        .then(html => { listBody.innerHTML = html; });
}

// 2. Khi bấm nút "Đơn nghỉ phép" ở bảng dưới
function viewLeaveRequests() {
    const date = document.getElementById('current-session-date').value;
    const classId = document.getElementById('current-class-id').value;

    console.log("Ngày lấy được: " + date);
    console.log("ID Lớp lấy được: " + classId);

    const modal = document.getElementById('modalLeaveDetail');
    if (!modal) {
        alert("Lỗi: Không tìm thấy thẻ HTML có id='modalLeaveDetail'!");
        return;
    }

    document.body.appendChild(modal);

    // HIỆN MODAL
    modal.style.display = 'flex'; // Ép nó hiện ra theo kiểu flex
    document.body.style.overflow = 'hidden'; // Khóa cuộn trang nền

    // Gán tiêu đề
    document.getElementById('leave_modal_title').innerText = `Sinh viên nghỉ ngày ${date}`;
    
    // Nạp dữ liệu AJAX
    const body = document.getElementById('leave_list_body');
    body.innerHTML = '<tr><td colspan="4" style="text-align:center;">Đang tải...</td></tr>';

    fetch(`tabs/process/get_leave_list.php?date=${date}&class_id=${classId}`)
        .then(response => response.text())
        .then(html => {
            body.innerHTML = html;
        });
}
/**
 * Cập nhật Text trạng thái khi tích vào nút radio
 */
function updateStatusText(studentId, statusValue) {
    const label = document.getElementById(`status-text-${studentId}`);
    if (!label) return;

    let config = { text: "Chưa điểm danh", color: "#999" };
    switch (statusValue) {
        case 'Present':   config = { text: "Có mặt", color: "#28a745" }; break;
        case 'Late':      config = { text: "Muộn/Về sớm", color: "#fd7e14" }; break;
        case 'Excused':   config = { text: "Vắng có phép", color: "#007bff" }; break;
        case 'Unexcused': config = { text: "Vắng không phép", color: "#dc3545" }; break;
    }
    label.innerText = config.text;
    label.style.color = config.color;
}

/**
 * ============================================================
 * 5. TIỆN ÍCH & LẮNG NGHE SỰ KIỆN HỆ THỐNG
 * ============================================================
 */

// Xóa nội dung input nhanh
function clearInput(btn) {
    const input = btn.previousElementSibling;
    if (input) {
        input.value = '';
        input.focus();
        input.dispatchEvent(new Event('input'));
    }
}

// Xử lý phím tắt ESC
document.addEventListener('keydown', (e) => {
    if (e.key === "Escape") {
        closeClassModal();
        closeSessionModal();
        closeLeaveModal();
    }
});

// Click ra ngoài để đóng các Modal
window.onclick = function(event) {
    const modals = [
        document.getElementById('classDetailsModal'),
        document.getElementById('modalEditSession'),
        document.getElementById('modalLeaveDetail')
    ];
    modals.forEach(m => {
        if (event.target === m) {
            m.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
};
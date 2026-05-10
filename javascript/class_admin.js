/**
 * Mở Modal Sửa và đổ dữ liệu lớp học
 * @param {Object} data - Đối tượng chứa thông tin lớp học (từ PHP json_encode)
 */
function openEditModal(data) {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    modal.style.display = 'block';
    
    // Đổ dữ liệu vào các trường input
    // Lưu ý: Các ID phải khớp chính xác với ID trong file PHP (course.php)
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.class_name;
    document.getElementById('edit_code').value = data.class_code;
    document.getElementById('edit_group').value = data.group_id;
    document.getElementById('edit_semester').value = data.semester;
    document.getElementById('edit_status').value = data.status;

    // Đổ dữ liệu Giảng viên (nếu account_id là null thì để trống)
    const teacherSelect = document.getElementById('edit_account_id');
    if (teacherSelect) {
        teacherSelect.value = data.account_id ? data.account_id : "";
    }
}

/**
 * Mở Modal Thêm mới
 */
function openAddModal() {
    const modal = document.getElementById('addModal');
    if (modal) {
        // Reset form để xóa dữ liệu cũ nếu có
        modal.querySelector('form').reset();
        modal.style.display = 'block';
    }
}

/**
 * Đóng bất kỳ Modal nào bằng ID
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

/**
 * Click ra ngoài vùng Modal để đóng
 */
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
};
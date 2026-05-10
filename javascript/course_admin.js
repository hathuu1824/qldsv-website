// 1. Bộ lọc Khoa (Giữ nguyên vì bạn vẫn lọc theo Khoa)
function filterByFaculty(facultyId) {
    let url = new URL(window.location.href);
    
    if (facultyId) {
        url.searchParams.set('faculty', facultyId);
    } else {
        url.searchParams.delete('faculty');
    }
    // Khi đổi khoa, nên xóa tham số search để tránh xung đột dữ liệu
    url.searchParams.delete('search'); 
    
    window.location.href = url.href;
}

// 2. Xử lý nút Enter trong ô tìm kiếm
const searchInput = document.querySelector('input[name="search"]');
if(searchInput) {
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Ngăn chặn hành vi mặc định
            this.form.submit();
        }
    });
}

/**
 * 3. Mở Modal Sửa và đổ dữ liệu Học phần
 * @param {Object} data - Dữ liệu từ dòng (row) được trả về từ json_encode($row)
 */
function openEditModal(data) {
    // Hiện modal
    document.getElementById('editModal').style.display = 'block';
    
    // Đổ dữ liệu vào các input tương ứng trong Modal Sửa
    // Lưu ý: ID của các phần tử này phải khớp với thuộc tính 'id' trong HTML của Modal
    if (document.getElementById('edit_id')) 
        document.getElementById('edit_id').value = data.id;
        
    if (document.getElementById('edit_code')) 
        document.getElementById('edit_code').value = data.subject_code;
        
    if (document.getElementById('edit_name')) 
        document.getElementById('edit_name').value = data.subject_name;
        
    if (document.getElementById('edit_credit')) 
        document.getElementById('edit_credit').value = data.credit;
        
    if (document.getElementById('edit_major_id')) 
        document.getElementById('edit_major_id').value = data.major_id;
        
    if (document.getElementById('edit_is_e')) 
        document.getElementById('edit_is_e').value = data.is_e;

    // Nếu bạn có trường description (mô tả)
    const descField = document.getElementById('edit_description') || document.getElementById('edit_desc');
    if (descField) 
        descField.value = data.description || '';
}

// 4. Đóng Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// 5. Đóng Modal khi click ra ngoài vùng nội dung (vùng xám)
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}
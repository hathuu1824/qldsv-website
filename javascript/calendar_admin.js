// 1. Hàm lọc khoa
function filterByFaculty(facultyId) {
    if (!facultyId) return;
    window.location.href = 'calendar.php?faculty=' + facultyId;
}

// 2. Hàm mở Modal Sửa (Giải quyết việc không hiện tên học phần)
function openEditModal(data) {
    console.log("Data received:", data); // Kiểm tra dữ liệu ở F12
    const modal = document.getElementById('editModal');
    if (!modal) return;

    modal.style.display = 'block';

    // Đổ dữ liệu - Phải dùng đúng ID đã khai báo trong HTML
    if(document.getElementById('edit_id')) document.getElementById('edit_id').value = data.id;
    if(document.getElementById('edit_class_display')) document.getElementById('edit_class_display').value = data.subject_name;
    if(document.getElementById('edit_date')) document.getElementById('edit_date').value = data.exam_date;
    if(document.getElementById('edit_time')) document.getElementById('edit_time').value = data.exam_time;
    if(document.getElementById('edit_room')) document.getElementById('edit_room').value = data.room;
}

// 3. Hàm xem danh sách sinh viên
function viewParticipants(sessionId, subjectName) {
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('participants-content');
    const title = document.getElementById('modal-title');

    if (!modal || !content) return;

    if (title) title.innerText = "Danh sách SV môn: " + subjectName;
    modal.style.display = 'block';
    content.innerHTML = "<p style='text-align:center;'>Đang tải...</p>";

    fetch('get_participants.php?session_id=' + sessionId)
        .then(res => res.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = "Lỗi tải dữ liệu!"; });
}

// 4. Các hàm đóng mở cơ bản
function openAddModal() { document.getElementById('addModal').style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// 5. Chống lỗi Null cho ô tìm kiếm
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) { 
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
});

// Click ra ngoài đóng modal
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Đọc tham số 'msg' từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    // 2. Nếu có 'msg', hiện thông báo tương ứng
    if (msg) {
        let text = "";
        
        switch (msg) {
            case 'delete_success':
                text = "✅ Đã xóa lịch thi thành công!";
                break;
            case 'error_active_students':
                text = "❌ KHÔNG THỂ XÓA!\nLý do: Vẫn còn sinh viên chưa tốt nghiệp trong lớp này.\nBạn phải giữ lại lịch thi để đối soát dữ liệu.";
                break;
            case 'system_error':
                text = "❌ Lỗi hệ thống! Không thể thực hiện lệnh xóa lúc này.";
                break;
            case 'add_success':
                text = "✅ Đã thêm lịch thi mới thành công!";
                break;
            case 'update_success':
                text = "✅ Đã cập nhật thay đổi thành công!";
                break;
        }

        // Hiện popup đơn giản (Dùng alert của trình duyệt)
        if (text) {
            alert(text);
        }

        // 3. Dọn dẹp URL (Xóa cái ?msg=... đi để khi F5 không bị hiện lại popup)
        const newUrl = window.location.pathname + (urlParams.get('faculty') ? '?faculty=' + urlParams.get('faculty') : '');
        window.history.replaceState({}, document.title, newUrl);
    }
});
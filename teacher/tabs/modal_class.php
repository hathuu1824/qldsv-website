<?php
/** @var array $class_data */
/** @var int $class_id */
/** @var int $sem_num */
/** @var int $year_start */
/** @var int $year_end */
?>
<style>
    .modal-new {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }
    .modal-content-new {
        background: #ffffff;
        margin: 5vh auto;
        width: 90%;
        max-width: 1000px;
        height: auto; 
        max-height: 100vh;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        overflow: hidden;
        animation: modalSlideDown 0.3s ease-out;
    }
    @keyframes modalSlideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-header-new {
        padding: 15px 25px;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #dee2e6;
    }
    .breadcrumb-modal {
        font-size: 24px;
        color: #333333;
        font-weight: 500;
    }
    .btn-close-modal {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
        line-height: 1;
    }
    .modal-nav-tabs {
        display: flex;
        background: #fff;
        padding: 0 20px;
        border-bottom: 1px solid #eee;
    }
    .tab-btn {
        padding: 15px 20px;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 14px;
        color: #495057;
        position: relative;
        transition: 0.2s;
    }
    .tab-btn.active {
        color: #007bff;
        font-weight: 600;
    }
    .tab-btn.active::after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #007bff;
    }
    .modal-body-new {
        flex: 1;
        overflow-y: auto;
        padding: 30px;
    }
    .tab-pane-modal {
        display: none;
    }
    .tab-pane-modal.active {
        display: block;
    }
    .info-section {
        margin-bottom: 30px;
    }
    .section-title-modal {
        margin-bottom: 20px;
        color: #333333;
        border-left: 4px solid #007bff;
        padding-left: 12px;
    }
    .info-grid-modal {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 40px;
    }
    .info-item{
        display: flex;
        flex-direction: row;
        gap: 10px;
        margin-top: 5px;
    }
    .info-item label {
        color: #888888;
        margin-bottom: 0;
        font-weight: 500;
    }
    .info-item span {
        font-weight: 500;
        color: #333333;
    }
    .info-item input {
        width: 100%;
        padding: 8px;
        border: 1px solid #007bff;
        border-radius: 4px;
        display: none;
        box-sizing: border-box;
    }
    .edit-state { 
        display: none !important; 
    }
    .is-editing .edit-state { 
        display: block !important; 
    }
    .is-editing .edit-state input.input-with-clear {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .is-editing .view-state { 
        display: none !important; 
    }
    .editing .info-item span {
        display: none;
    }
    .editing .info-item input {
        display: block;
    }
    .text-preview {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        line-height: 1.6;
        color: #444;
        border: 1px solid #eee;
    }
    .editing .info-item span {
        display: none;
    }
    .editing .info-item input {
        display: block;
    }
    .textarea-modal {
        width: 100%;
        height: 200px; 
        min-height: 150px; 
        max-height: 350px;
        padding: 15px;
        border: 1px solid #dddddd;
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.6;
        resize: vertical;
    }
    .modal-footer-new .edit-state{
        padding: 15px 25px;
        border-top: 1px solid #eeeeee;
        text-align: right;
        background: #ffffff;
    }
    .btn-save-all {
        background: #28a745;
        color: #ffffff;
        border: none;
        padding: 10px 25px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-cancel-modal {
        background: #6c757d;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
    }
    .btn-edit-toggle {
        background: #007bff;
        color: #fff;
        border: none;
        padding: 6px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
    }
</style>

<!-- Modal tab thông tin chung -->
<div id="classDetailsModal" class="modal-new">
    <div class="modal-content-new">
        <form id="mainUpdateForm" class="is-viewing" action="process/update_full_class.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">

            <div class="modal-header-new">
                <div class="header-left">
                    <span class="breadcrumb-modal">Chi tiết học phần</span>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeClassModal()">&times;</button>
            </div>

            <div class="modal-nav-tabs">
                <button type="button" class="tab-btn active" onclick="switchModalTab(event, 'tab-info')">Thông tin chung</button>
                <button type="button" class="tab-btn" onclick="switchModalTab(event, 'tab-goals')">Mục tiêu</button>
                <button type="button" class="tab-btn" onclick="switchModalTab(event, 'tab-weights')">Trọng số</button>
                <button type="button" class="tab-btn" onclick="switchModalTab(event, 'tab-materials')">Học liệu</button>
                <button type="button" class="tab-btn" onclick="switchModalTab(event, 'tab-links')">Học trực tuyến</button>
            </div>

            <div class="modal-body-new">
                <div id="tab-info" class="tab-pane-modal active">
                    <div class="info-section" id="general-info-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 class="section-title-modal" style="margin:0;">Thông tin học phần</h4>
                            <button type="button" class="btn-edit-toggle view-state" onclick="enableEditMode()">Chỉnh sửa</button>
                        </div>
                        <div class="info-grid-modal">
                            <div class="info-item">
                                <label>Mã lớp học:</label>
                                <span class="view-state"><?= htmlspecialchars($class_data['class_code']) ?></span>
                                <input type="text" name="class_code" class="edit-state" value="<?= htmlspecialchars($class_data['class_code']) ?>" readonly>
                            </div>
                            <div class="info-item">
                                <label>Tên môn học:</label>
                                <span class="view-state"><?= htmlspecialchars($class_data['subject_name']) ?></span>
                                <input type="text" name="subject_name" class="edit-state" value="<?= htmlspecialchars($class_data['subject_name']) ?>">
                            </div>
                            <div class="info-item">
                                <label>Số tín chỉ:</label>
                                <span class="view-state"><?= $class_data['credit'] ?> tín chỉ</span>
                                <input type="number" name="credit" class="edit-state" value="<?= $class_data['credit'] ?>">
                            </div>
                            <div class="info-item">
                                <label>Học kỳ hiện tại:</label>
                                <span class="view-state">Kỳ <?= $sem_num ?> (<?= $year_start ?> - <?= $year_end ?>)</span>
                                <input type="text" class="edit-state" value="Kỳ <?= $sem_num ?> (<?= $year_start ?> - <?= $year_end ?>)" readonly style="background-color: #f1f3f5; color: #666; cursor: not-allowed; border: 1px solid #dee2e6;">
                            </div>
                        </div>
                    </div>
                    <div class="info-section">
                        <h4 class="section-title-modal">Thông tin đề cương</h4>
                        <div class="file-box">
                            <div class="info-item">
                                <label>Đề cương chi tiết:</label>
                                <?php if(!empty($class_data['syllabus_path'])): ?>
                                        <a href="../uploads/syllabus/<?= $class_data['syllabus_path'] ?>" 
                                        target="_blank" 
                                        style="color: #007bff; text-decoration: underline; font-weight: 500;">
                                        <?= $class_data['syllabus_path'] ?>
                                        </a>
                                    <?php else: ?>
                                    <span class="view-state">Chưa cập nhật</span>
                                <?php endif; ?>
                                <input type="file" name="syllabus_file" class="edit-state" style="margin-left: 10px; width: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-goals" class="tab-pane-modal">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 class="section-title-modal">Mục tiêu môn học</h4>
                    <button type="button" class="btn-edit-toggle view-state" onclick="enableEditMode()">Chỉnh sửa</button>
                    </div>
                    <div class="view-state text-preview"><?= nl2br(htmlspecialchars($class_data['goals'] ?? 'Chưa cập nhật')) ?></div>
                    <textarea name="goals" class="edit-state textarea-modal" rows="12" placeholder="Điền mục tiêu môn học tại đây..."><?= htmlspecialchars($class_data['goals'] ?? '') ?></textarea>
                </div>

                <div id="tab-weights" class="tab-pane-modal">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 class="section-title-modal">Cấu trúc điểm & Trọng số</h4>
                        <button type="button" class="btn-edit-toggle view-state" onclick="enableEditMode()">Chỉnh sửa</button>
                    </div>
                    <div class="view-state text-preview"><?= nl2br(htmlspecialchars($class_data['weights'] ?? 'Chưa cập nhật')) ?></div>
                    <textarea name="weights" class="edit-state textarea-modal" rows="12" placeholder="Ví dụ: Chuyên cần 10%, Giữa kỳ 30%, Cuối kỳ 60%..."><?= htmlspecialchars($class_data['weights'] ?? '') ?></textarea>
                </div>

                <div id="tab-materials" class="tab-pane-modal">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 class="section-title-modal">Sách, Giáo trình & Tài liệu tham khảo</h4>
                        <button type="button" class="btn-edit-toggle view-state" onclick="enableEditMode()">Chỉnh sửa</button>
                    </div>
                    <div class="view-state text-preview"><?= nl2br(htmlspecialchars($class_data['materials'] ?? 'Chưa cập nhật')) ?></div>
                    <textarea name="materials" class="edit-state textarea-modal" rows="12" placeholder="Liệt kê danh sách tài liệu học tập..."><?= htmlspecialchars($class_data['materials'] ?? '') ?></textarea>
                </div>

                <div id="tab-links" class="tab-pane-modal">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h4 class="section-title-modal" style="margin:0;">Học trực tuyến</h4>
                        <button type="button" class="btn-edit-toggle view-state" onclick="enableEditMode()">✎ Chỉnh sửa</button>
                    </div>
                    <div class="info-section">
                        <div class="file-box" style="padding: 25px;">
                            <div class="info-item" style="display: flex; align-items: center; position: relative;">
                                <label style="min-width: 140px; font-weight: 600; color: #555;">Link trực tuyến:</label>
                                <div class="view-state" style="flex: 1;">
                                    <?php if(!empty($class_data['online_link'])): ?>
                                        <a href="<?= htmlspecialchars($class_data['online_link']) ?>" 
                                        target="_blank" 
                                        style="color: #28a745; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                        Vào phòng học trực tuyến
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">Chưa có đường dẫn nào được cập nhật.</span>
                                    <?php endif; ?>
                                </div>
                                <div class="edit-state" style="flex: 1; display: none; align-items: center; position: relative;">
                                    <input type="url" 
                                        name="online_link" 
                                        class="input-with-clear"
                                        value="<?= htmlspecialchars($class_data['online_link'] ?? '') ?>" 
                                        placeholder="Dán link Google Meet, Zoom hoặc Teams vào đây..." 
                                        style="width: 100%; padding: 10px 35px 10px 12px; border: 1px solid #007bff; border-radius: 6px; font-size: 14px; outline: none; box-shadow: 0 2px 4px rgba(0,123,255,0.1);">
                                </div>
                            </div>
                            <div class="edit-state" style="margin-left: 140px; margin-top: 8px;">
                                <small style="color: #6c757d;">Lưu ý: Link phải bắt đầu bằng http:// hoặc https://</small>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="modal-footer-new">
                <div class="edit-state">
                    <button type="button" class="btn-cancel-modal" style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; margin-right: 10px;" onclick="disableEditMode()">Hủy bỏ</button>
                    <button type="submit" name="btn_save_full" class="btn-save-all">Lưu tất cả thay đổi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal tab lịch giảng dạy -->
<div id="modalLeaveDetail" class="modal-new" style="display: none; position: fixed !important; z-index: 999999 !important; background-color: rgba(0,0,0,0.7);">
    <div class="modal-content-new" style="max-width: 700px;">
        <div class="modal-header-new">
            <h3 id="leave_modal_title">Danh sách đơn nghỉ phép</h3>
            <span class="close-new" onclick="closeLeaveModal()">&times;</span>
        </div>
        <div class="modal-body-new">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mã SV</th>
                        <th>Họ và tên</th>
                        <th>Lý do</th>
                        <th>Minh chứng</th>
                    </tr>
                </thead>
                <tbody id="leave_list_body">
                    </tbody>
            </table>
        </div>
    </div>
</div>
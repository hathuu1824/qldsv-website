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
    .info-item {
        display: flex;
        flex-direction: row;
        gap: 10px;
        margin-top: 5px;
    }
    .info-item label {
        color: #888888;
        font-weight: 500;
        min-width: 120px;
    }
    .info-item span {
        font-weight: 500;
        color: #333333;
    }
    .text-preview {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        line-height: 1.6;
        color: #444;
        border: 1px solid #eee;
        white-space: pre-line;
    }
</style>

<div id="classDetailsModal" class="modal-new">
    <div class="modal-content-new">
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
                <div class="info-section">
                    <h4 class="section-title-modal">Thông tin học phần</h4>
                    <div class="info-grid-modal">
                        <div class="info-item">
                            <label>Mã lớp học:</label>
                            <span><?= htmlspecialchars($class_data['class_code']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Tên môn học:</label>
                            <span><?= htmlspecialchars($class_data['subject_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Số tín chỉ:</label>
                            <span><?= $class_data['credit'] ?> tín chỉ</span>
                        </div>
                        <div class="info-item">
                            <label>Học kỳ hiện tại:</label>
                            <span>Kỳ <?= $sem_num ?> (<?= $year_start ?> - <?= $year_end ?>)</span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4 class="section-title-modal">Đề cương chi tiết</h4>
                    <div class="text-preview">
                        <?php if(!empty($class_data['syllabus_path'])): ?>
                            <i class="fas fa-file-pdf" style="color: #e74c3c;"></i> 
                            <a href="../uploads/syllabus/<?= $class_data['syllabus_path'] ?>" 
                               target="_blank" 
                               style="color: #007bff; text-decoration: none; font-weight: 500;">
                               Tải xuống đề cương (.pdf)
                            </a>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Chưa có đề cương được cập nhật.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="tab-goals" class="tab-pane-modal">
                <h4 class="section-title-modal">Mục tiêu môn học</h4>
                <div class="text-preview">
                    <?= !empty($class_data['goals']) ? nl2br(htmlspecialchars($class_data['goals'])) : 'Chưa có thông tin mục tiêu.' ?>
                </div>
            </div>

            <div id="tab-weights" class="tab-pane-modal">
                <h4 class="section-title-modal">Cấu trúc điểm & Trọng số</h4>
                <div class="text-preview">
                    <?= !empty($class_data['weights']) ? nl2br(htmlspecialchars($class_data['weights'])) : 'Chưa có thông tin trọng số.' ?>
                </div>
            </div>

            <div id="tab-materials" class="tab-pane-modal">
                <h4 class="section-title-modal">Tài liệu tham khảo</h4>
                <div class="text-preview">
                    <?= !empty($class_data['materials']) ? nl2br(htmlspecialchars($class_data['materials'])) : 'Chưa có thông tin học liệu.' ?>
                </div>
            </div>

            <div id="tab-links" class="tab-pane-modal">
                <h4 class="section-title-modal">Phòng học trực tuyến</h4>
                <div class="text-preview" style="text-align: center; padding: 40px;">
                    <?php if(!empty($class_data['online_link'])): ?>
                        <p style="margin-bottom: 20px; color: #666;">Giảng viên đã cập nhật đường dẫn học trực tuyến:</p>
                        <a href="<?= htmlspecialchars($class_data['online_link']) ?>" 
                           target="_blank" 
                           style="background: #28a745; color: #fff; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px;">
                           <i class="fas fa-video"></i> Vào phòng học trực tuyến
                        </a>
                    <?php else: ?>
                        <div style="color: #999;">
                            <i class="fas fa-link-slash" style="font-size: 40px; display: block; margin-bottom: 15px;"></i>
                            <p>Lớp học hiện tại chưa có link học trực tuyến.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="modal-header-new" style="border-top: 1px solid #dee2e6; border-bottom: none; justify-content: flex-end;">
            <button type="button" class="btn-cancel-modal" style="background:#6c757d; color:white; border:none; padding:10px 25px; border-radius:4px; cursor:pointer;" onclick="closeClassModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
    // Hàm chuyển đổi Tab trong Modal
    function switchModalTab(evt, tabId) {
        const panes = document.querySelectorAll('.tab-pane-modal');
        const btns = document.querySelectorAll('.tab-btn');
        
        panes.forEach(p => p.classList.remove('active'));
        btns.forEach(b => b.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        evt.currentTarget.classList.add('active');
    }

    // Hàm đóng Modal
    function closeClassModal() {
        document.getElementById('classDetailsModal').style.display = 'none';
    }
</script>
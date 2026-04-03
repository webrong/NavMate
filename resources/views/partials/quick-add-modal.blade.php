<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">快捷添加站点</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quick-add-form">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">网址 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <img id="quick-add-preview" src="" alt="" style="display:none; width:32px; height:32px; object-fit:contain; border-radius:4px;">
                            <input type="url" class="form-control" id="quick-add-url" name="url" placeholder="https://example.com" required>
                        </div>
                        <div class="form-text">粘贴网址后自动获取标题和图标</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">标题</label>
                        <input type="text" class="form-control" id="quick-add-title" name="title" placeholder="自动获取...">
                    </div>
                    <input type="hidden" id="quick-add-favicon" name="favicon_url">
                    <div class="mb-3">
                        <label class="form-label">分类 <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">选择分类</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary btn-sm">添加</button>
                </div>
            </form>
        </div>
    </div>
</div>

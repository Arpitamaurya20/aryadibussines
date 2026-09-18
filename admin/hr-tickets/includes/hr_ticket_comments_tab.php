<?php
/** @var array $ticket */
/** @var Hrticket $hr */
/** @var bool $canManage */
/** @var bool $isOwner */
$comments = $hr->getComments((int) ($ticket['id'] ?? 0), $canManage);
$isClosed = (string) ($ticket['status'] ?? '') === 'closed';
$canReply = !$isClosed && ($isOwner || $canManage);
?>
<div class="row">
    <div class="col-lg-8">
        <h6 class="font-weight-bold mb-3">Conversation</h6>
        <div class="hr-comment-list" id="hr_comment_list">
            <?php if (empty($comments)) { ?>
            <p class="text-muted">No comments yet. HR will respond after reviewing your ticket.</p>
            <?php } else {
                foreach ($comments as $c) {
                    $isInternal = !empty($c['is_internal']);
                    $roleLabel = ($c['author_role'] ?? '') === 'hr' ? 'HR' : 'Employee';
                    $author = $c['author_name'] ?? $c['author_username'] ?? $roleLabel;
                    ?>
            <div class="hr-comment-item<?= $isInternal ? ' hr-internal' : ''; ?>">
                <div class="hr-comment-meta">
                    <span><strong><?= htmlspecialchars($author); ?></strong> · <?= htmlspecialchars($roleLabel); ?><?= $isInternal ? ' · Internal note' : ''; ?></span>
                    <span><?= htmlspecialchars($c['created_at'] ?? ''); ?></span>
                </div>
                <div class="hr-comment-body"><?= nl2br(htmlspecialchars($c['comment_text'] ?? '')); ?></div>
            </div>
            <?php }
            } ?>
        </div>
    </div>
    <div class="col-lg-4">
        <?php if ($canReply) { ?>
        <div class="hr-card">
            <div class="hr-card-header"><strong>Add reply</strong></div>
            <div class="p-3">
                <form id="hr_add_comment_form">
                    <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                    <div class="form-group">
                        <textarea name="comment_text" id="hr_comment_text" class="form-control" rows="4" required placeholder="Type your message…"></textarea>
                    </div>
                    <?php if ($canManage) { ?>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" name="is_internal" id="hr_is_internal" value="1">
                        <label class="form-check-label" for="hr_is_internal">Internal note (HR only)</label>
                    </div>
                    <?php } ?>
                    <button type="submit" class="btn btn-primary btn-sm btn-block" id="hr_comment_submit_btn">
                        <i class="fal fa-reply mr-1"></i> Send
                    </button>
                </form>
                <hr>
                <form id="hr_upload_attachment_form" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Attach file (max 10 MB)</label>
                        <input type="file" name="attachment" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt" required>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm btn-block" id="hr_upload_btn">
                        <i class="fal fa-upload mr-1"></i> Upload
                    </button>
                </form>
            </div>
        </div>
        <?php } elseif ($isOwner && (string) ($ticket['status'] ?? '') === 'resolved') { ?>
        <div class="hr-card">
            <div class="p-3">
                <p class="small text-muted">If your concern is resolved, close this ticket.</p>
                <button type="button" class="btn btn-success btn-block" id="hr_close_ticket_btn" data-ticket-id="<?= (int) ($ticket['id'] ?? 0); ?>">
                    <i class="fal fa-check-circle mr-1"></i> Close ticket
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

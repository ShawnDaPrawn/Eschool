<?php

// Add types with their feature. Modes are mandatory, default_on, default_off,
// or off (not offered). The platform locks cannot be weakened by school rows.
$all = ['learner', 'parent', 'teacher', 'admin'];
$on = ['push' => 'default_on', 'email' => 'default_off', 'in_app' => 'default_on'];
$mail = ['push' => 'off', 'email' => 'mandatory', 'in_app' => 'off'];
$required = ['push' => 'mandatory', 'email' => 'mandatory', 'in_app' => 'mandatory'];

return [
    'account.verify_email' => ['label' => 'Verify email', 'module' => 'people', 'portals' => $all, 'channels' => $mail, 'locked' => $all],
    'account.password_reset' => ['label' => 'Password reset', 'module' => 'people', 'portals' => $all, 'channels' => $mail, 'locked' => $all],
    'account.invitation' => ['label' => 'School invitation', 'module' => 'people', 'portals' => $all, 'channels' => $mail, 'locked' => $all],
    'account.sign_in_link' => ['label' => 'Sign-in link', 'module' => 'people', 'portals' => $all, 'channels' => $mail, 'locked' => $all],
    'account.new_device' => ['label' => 'New sign-in', 'module' => 'people', 'portals' => $all, 'channels' => $required, 'locked' => $all],
    'approval.requested' => ['label' => 'Learner access approval', 'module' => 'people', 'portals' => ['parent'], 'channels' => $required, 'locked' => ['parent']],
    'approval.handle_requested' => ['label' => 'Handle approval request', 'module' => 'people', 'portals' => ['parent'], 'channels' => $required, 'locked' => ['parent']],
    'consent.requested' => ['label' => 'Consent request', 'module' => 'people', 'portals' => $all, 'channels' => $required, 'locked' => $all],
    'safeguarding.alert' => ['label' => 'Safeguarding alert', 'module' => 'wellbeing', 'portals' => ['admin', 'teacher'], 'channels' => $required, 'locked' => ['admin', 'teacher'], 'permission' => 'safeguarding-alerts'],
    'comms.notice' => ['label' => 'School notice', 'module' => 'communication', 'portals' => $all, 'channels' => $on],
    'comms.urgent' => ['label' => 'Urgent school notice', 'module' => 'communication', 'portals' => $all, 'channels' => $required, 'locked' => $all],
    'communication.message' => ['label' => 'School message', 'module' => 'communication', 'portals' => $all, 'channels' => $on],
    'communication.approval_requested' => ['label' => 'Message awaiting approval', 'module' => 'communication', 'portals' => ['teacher', 'admin'], 'channels' => $on],
    'comms.message' => ['label' => 'Direct message', 'module' => 'chat', 'portals' => $all, 'channels' => $on],
    'academics.assignment_published' => ['label' => 'Assignment published', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'academics.submission' => ['label' => 'Assignment submission', 'module' => 'academics', 'portals' => ['teacher', 'admin'], 'channels' => $on],
    'academics.content_published' => ['label' => 'Lesson content', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'academics.marks_released' => ['label' => 'Marks released', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'academics.content_published' => ['label' => 'Learning content published', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'academics.report_published' => ['label' => 'Report published', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'academics.register_amended' => ['label' => 'Register amended', 'module' => 'academics', 'portals' => $all, 'channels' => $on],
    'attendance.absence' => ['label' => 'Absence alert', 'module' => 'academics', 'portals' => $all, 'channels' => $on, 'locked' => ['parent'], 'mandatory_channels' => ['push', 'email', 'in_app']],
    'fees.invoice' => ['label' => 'Fee invoice', 'module' => 'fees', 'portals' => $all, 'channels' => array_replace($on, ['email' => 'default_on'])],
    'fees.reminder' => ['label' => 'Fee reminder', 'module' => 'fees', 'portals' => $all, 'channels' => array_replace($on, ['email' => 'default_on'])],
    'fees.receipt' => ['label' => 'Payment receipt', 'module' => 'fees', 'portals' => $all, 'channels' => array_replace($on, ['email' => 'default_on'])],
    'wellbeing.staff_update' => ['label' => 'Private staff updates', 'module' => 'wellbeing', 'portals' => ['teacher', 'admin'], 'channels' => $on, 'permission' => 'pastoral-sensitive-access'],
    'wellbeing.report_reply' => ['label' => 'Private report replies', 'module' => 'wellbeing', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'transfer.consent_requested' => ['label' => 'School transfer review', 'module' => 'school_transfers', 'portals' => ['parent'], 'channels' => $on],
    'progress.staff_band_changed' => ['label' => 'Teaching progress updates', 'module' => 'academics', 'portals' => ['teacher', 'admin'], 'channels' => $on],
    'progress.band_changed' => ['label' => 'Learning progress updates', 'module' => 'academics', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'pastoral.cover_assigned' => ['label' => 'Relief cover assignment', 'module' => 'relief_cover', 'portals' => ['teacher', 'admin'], 'channels' => $on],
    'recognition.points_changed' => ['label' => 'House point updates', 'module' => 'house_points', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'recognition.award_presented' => ['label' => 'Award presentations', 'module' => 'awards', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'consultations.opened' => ['label' => 'School consultations', 'module' => 'consultations', 'portals' => ['learner', 'parent'], 'channels' => $on],
    'consultations.staff_opened' => ['label' => 'Staff school consultations', 'module' => 'consultations', 'portals' => ['teacher', 'admin'], 'channels' => $on],
];

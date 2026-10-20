<?php

namespace App\Enums;

enum NotificationType: string
{
    // User notifications

    case SubscribedUserUsernameChange = 'subscribed_user_username_change';
    case NewFollower = 'new_follower';
    case NewFollowerPost = 'new_follower_post';
    case NewGroupPost = 'new_group_post';
    case AcceptedGroupInvite = 'accepted_group_invite';
    case DeclinedGroupInvite = 'declined_group_invite';
    case NewCommentUnderPost = 'new_comment_under_post';
    case NewCommentAnswer = 'new_comment_answer';

    case ReportCreated = 'report_created';
    case ReportReviewed = 'report_reviewed';

    // ../Important notifications

    case ReceivedWarning = 'received_warning';
    case WarningRemoved = 'warning_removed';
    case GroupBan = 'group_ban';
    case GroupUnban = 'group_unban';
    case PlatformBan = 'platform_ban';
    case PlatformUnban = 'platform_unban';

    // Moderator notifications

    case ModeratorRoleInvite = 'moderator_role_invite';
    case GroupModeratorWelcome = 'group_moderator_welcome';
    case NewModerationCase = 'new_moderation_case';
    case ModerationCaseClosed = 'moderation_case_closed';

    // Admin notifications

    case AdminWelcome = 'admin_welcome';
    case NewAdminCase = 'new_admin_case';
    case AdminCaseClosed = 'admin_case_closed';

    public function category(): NotificationCategory
    {
        return match ($this) {
            self::ModeratorRoleInvite,
            self::GroupBan,
            self::GroupUnban,
            self::PlatformBan,
            self::PlatformUnban,
            self::ReceivedWarning,
            self::WarningRemoved => NotificationCategory::Important,
            default => NotificationCategory::Default,
        };
    }
}

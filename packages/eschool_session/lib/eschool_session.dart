/// Who is signed in and what the server says about them, shared by both
/// NavigateEd apps: the session store and auth mode, the `/me/context`
/// cubit, the force-update check, the deep link parser and where the app's
/// Hive boxes live.
library;

// The generated models of /me/context the session hands to the apps.
export 'package:eschool_api/eschool_api.dart'
    show
        ContextUser,
        CurrentSchool,
        NotificationRule,
        SchoolMembership,
        SchoolSummary;

export 'src/auth/auth_mode.dart';
export 'src/availability/endpoint_availability.dart';
export 'src/auth/session_dio.dart';
export 'src/auth/session_repository.dart';
export 'src/context/me_context.dart';
export 'src/context/me_context_cubit.dart';
export 'src/context/me_context_refresher.dart';
export 'src/context/me_context_repository.dart';
export 'src/deep_links/deep_link_coordinator.dart';
export 'src/deep_links/deep_link_parser.dart';
export 'src/deep_links/push_link.dart';
export 'src/selection/child_selection_cubit.dart';
export 'src/selection/child_switcher.dart';
export 'src/selection/school_header.dart';
export 'src/selection/school_selection_cubit.dart';
export 'src/selection/school_switcher.dart';
export 'src/selection/selection_store.dart';
export 'src/storage/app_storage.dart';
export 'src/update/force_update.dart';

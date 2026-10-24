import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/foundation.dart';

/// The portal addresses and app links this app understands, each mapped to
/// the existing route of the same screen. A route's own name works as a path
/// too, so reloading a page in the browser lands on it again.
const List<DeepLinkRoute> deepLinkRoutes = [
  DeepLinkRoute(
    Routes.schoolMessages,
    Routes.schoolMessages,
    arguments: messageLinkArguments,
  ),
  DeepLinkRoute(
    '/communication-centre',
    Routes.schoolMessages,
    arguments: messageLinkArguments,
  ),
  DeepLinkRoute(
    Routes.academicContent,
    Routes.academicContent,
    arguments: contentLinkArguments,
  ),
  DeepLinkRoute(Routes.academicReports, Routes.academicReports),
  DeepLinkRoute(Routes.academicMarks, Routes.academicMarks),
  DeepLinkRoute('/notifications', Routes.notifications),
  DeepLinkRoute('/notices', Routes.announcements),
  DeepLinkRoute(Routes.announcements, Routes.announcements),
  DeepLinkRoute(Routes.assignments, Routes.assignments),
  DeepLinkRoute('/calendar', Routes.academicCalendar),
  DeepLinkRoute(Routes.academicCalendar, Routes.academicCalendar),
  DeepLinkRoute('/exams', Routes.exams),
  DeepLinkRoute(Routes.exams, Routes.exams),
  DeepLinkRoute('/leaves', Routes.manageLeaves),
  DeepLinkRoute(Routes.manageLeaves, Routes.manageLeaves),
  DeepLinkRoute(Routes.manageStudentLeaves, Routes.manageStudentLeaves),
  DeepLinkRoute(Routes.teacherAttendence, Routes.teacherAttendence),
  DeepLinkRoute(Routes.gallery, Routes.gallery),
  DeepLinkRoute('/about', Routes.aboutUs),
  DeepLinkRoute(Routes.aboutUs, Routes.aboutUs),
  DeepLinkRoute('/privacy', Routes.privacyPolicy),
  DeepLinkRoute(Routes.privacyPolicy, Routes.privacyPolicy),
  DeepLinkRoute('/terms', Routes.termsAndCondition),
  DeepLinkRoute(Routes.termsAndCondition, Routes.termsAndCondition),
  DeepLinkRoute('/contact', Routes.contactUs),
  DeepLinkRoute(Routes.contactUs, Routes.contactUs),
  // The shared screens (Phase 1). A push's `link` names these paths too.
  DeepLinkRoute(Routes.notificationSettings, Routes.notificationSettings),
  DeepLinkRoute(Routes.mySchool, Routes.mySchool),
  DeepLinkRoute(Routes.profilePicture, Routes.profilePicture),
  DeepLinkRoute(Routes.handles, Routes.handles),
  DeepLinkRoute('/chat', Routes.chatUserPage),
];

/// Links that opened the app or arrive while it runs. A web build also
/// accepts its own origin, so the portal works on any host it is served
/// from.
final DeepLinkCoordinator deepLinks = DeepLinkCoordinator(
  parser: DeepLinkParser(
    routes: deepLinkRoutes,
    hosts: {...DeepLinkParser.defaultHosts, if (kIsWeb) Uri.base.host},
  ),
  source: AppLinksDeepLinkSource(),
);

/// Routes before the user is in: a link waits until they are past these.
const Set<String> _entryRoutes = {Routes.splash, Routes.login, Routes.policies};

/// Opens the screen a link asked for, if the user is signed in and past the
/// splash and sign-in screens; otherwise keeps it for [openPendingDeepLink].
void openDeepLink(DeepLinkTarget target, {required bool signedIn}) {
  final navigator = UiUtils.rootNavigatorKey.currentState;
  if (!signedIn ||
      navigator == null ||
      _entryRoutes.contains(Routes.currentRoute)) {
    deepLinks.keepPending(target);
    return;
  }
  navigator.pushNamed(target.routeName, arguments: target.arguments);
}

/// Called once the user lands on home: opens the screen of the link that
/// opened the app, if there was one.
void openPendingDeepLink() {
  final DeepLinkTarget? target = deepLinks.takePending();
  if (target == null) {
    return;
  }
  Future.microtask(
    () => UiUtils.rootNavigatorKey.currentState?.pushNamed(
      target.routeName,
      arguments: target.arguments,
    ),
  );
}

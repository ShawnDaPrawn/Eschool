import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool/app/routeArguments.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';

/// The portal addresses and app links this app understands, each mapped to
/// the existing route of the same screen. A route's own name works as a path
/// too, so reloading a page in the browser lands on it again.
const List<DeepLinkRoute> deepLinkRoutes = [
  DeepLinkRoute(Routes.tutor, Routes.tutor),
  DeepLinkRoute(Routes.tutorSummary, Routes.tutorSummary),
  DeepLinkRoute(
    Routes.consultations,
    Routes.consultations,
    arguments: consultationLinkArguments,
  ),
  DeepLinkRoute(
    '/consultations/:consultation',
    Routes.consultations,
    arguments: consultationLinkArguments,
  ),
  DeepLinkRoute(
    '/school-consultations/:consultation',
    Routes.consultations,
    arguments: consultationLinkArguments,
  ),
  DeepLinkRoute(Routes.familyDiscipline, Routes.familyDiscipline),
  DeepLinkRoute('/school-discipline', Routes.familyDiscipline),
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
  DeepLinkRoute(Routes.mastery, Routes.mastery),
  DeepLinkRoute(Routes.familyProgress, Routes.familyProgress),
  DeepLinkRoute(Routes.reportConcern, Routes.reportConcern),
  DeepLinkRoute(Routes.recognition, Routes.recognition),
  DeepLinkRoute(Routes.schoolTransfers, Routes.schoolTransfers),
  DeepLinkRoute(Routes.academicMarks, Routes.academicMarks),
  DeepLinkRoute('/notices', Routes.noticeBoard),
  DeepLinkRoute(Routes.noticeBoard, Routes.noticeBoard),
  DeepLinkRoute(Routes.notifications, Routes.notifications),
  DeepLinkRoute('/payments', Routes.feesTransaction),
  DeepLinkRoute(Routes.feesTransaction, Routes.feesTransaction),
  DeepLinkRoute(Routes.gallery, Routes.gallery),
  DeepLinkRoute(
    Routes.academicCalendar,
    Routes.academicCalendar,
    arguments: _academicCalendarArguments,
  ),
  DeepLinkRoute(Routes.settings, Routes.settings),
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
  DeepLinkRoute(Routes.approvals, Routes.approvals),
  DeepLinkRoute(
    '/invite/:token',
    Routes.invitation,
    arguments: _invitationToken,
  ),
  DeepLinkRoute(Routes.profilePicture, Routes.profilePicture),
  DeepLinkRoute(Routes.handles, Routes.handles),
  DeepLinkRoute('/chat', Routes.chatUserPage),
];

Object? _invitationToken(
  Map<String, String> values,
  Map<String, String> query,
) {
  final String token = values['token'] ?? '';
  if (token.isEmpty) {
    throw const FormatException('An invitation link carries its token.');
  }
  return token;
}

/// Links that open before sign-in: an invitation is how a new parent gets
/// an account.
const Set<String> _publicRoutes = {Routes.invitation};

Object? _academicCalendarArguments(
  Map<String, String> values,
  Map<String, String> query,
) => const AcademicCalendarArguments(hasBack: true);

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
const Set<String> _entryRoutes = {
  Routes.splash,
  Routes.auth,
  Routes.studentLogin,
  Routes.parentLogin,
  Routes.parentPolicies,
  Routes.studentFeePaymentDueScreen,
};

/// Opens the screen a link asked for, if the user is signed in and past the
/// splash and sign-in screens; otherwise keeps it for [openPendingDeepLink].
void openDeepLink(DeepLinkTarget target, {required bool signedIn}) {
  final navigator = UiUtils.rootNavigatorKey.currentState;
  if (_publicRoutes.contains(target.routeName) &&
      navigator != null &&
      Routes.currentRoute != Routes.splash) {
    navigator.pushNamed(target.routeName, arguments: target.arguments);
    return;
  }
  if (!signedIn ||
      navigator == null ||
      _entryRoutes.contains(Routes.currentRoute)) {
    deepLinks.keepPending(target);
    return;
  }
  if (target.routeName == Routes.academicContent ||
      target.routeName == Routes.schoolMessages) {
    navigator.popUntil((route) => route.isFirst);
  }
  navigator.pushNamed(target.routeName, arguments: target.arguments);
}

/// Called when the sign-in choice shows: opens an invitation link that
/// opened the app, if there was one. Other links wait for home.
void openPendingPublicDeepLink() {
  final DeepLinkTarget? target = deepLinks.takePendingWhere(
    (target) => _publicRoutes.contains(target.routeName),
  );
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

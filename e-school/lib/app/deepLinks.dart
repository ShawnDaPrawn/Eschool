import 'package:eschool/app/routeArguments.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';

/// The portal addresses and app links this app understands, each mapped to
/// the existing route of the same screen. A route's own name works as a path
/// too, so reloading a page in the browser lands on it again.
const List<DeepLinkRoute> deepLinkRoutes = [
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
];

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
